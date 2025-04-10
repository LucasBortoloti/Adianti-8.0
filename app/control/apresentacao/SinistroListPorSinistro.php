<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TDatabase;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class SinistroListPorSinistro extends TPage
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('defciv');          // defines the database
        $this->setActiveRecord('Ocorrencia');         // defines the active record
        $this->setDefaultOrder('id', 'asc');    // defines the default order
        $this->addFilterField('id', '=', 'id'); // filterField, operator, formField

        $this->addFilterField('date', '>=', 'date_from', function ($value) {
            return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        });

        $this->addFilterField('date', '<=', 'date_to', function ($value) {
            return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        });

        $this->form = new BootstrapFormBuilder('form_search_Ocorrencias');
        $this->form->setFormTitle(('Sinistros, baixados e abertos por tipo de sinistro'));

        // $id = new TEntry('id');
        $date_from = new TDate('date_from');
        $date_to = new TDate('date_to');

        $tipo_sinistro = new TDBCombo('tipo_sinistro', 'defciv', 'Sinistro', 'id', 'descricao');
        $pesquisa = new TRadioGroup('pesquisa');

        $this->form->addFields([new TLabel('De')], [$date_from]);
        $this->form->addFields([new TLabel('Até')], [$date_to]);
        $this->form->addFields([new TLabel('Tipo de Sinistro')], [$tipo_sinistro]);
        $this->form->addFields([new TLabel('Tipo de pesquisa')], [$pesquisa]);


        $date_from->addValidation('De', new TRequiredValidator);
        $date_to->addValidation('Até', new TRequiredValidator);
        $tipo_sinistro->addValidation('Tipo de Sinistro', new TRequiredValidator);
        $pesquisa->addValidation('Pesquisa', new TRequiredValidator);

        $tipo_sinistro->setSize('65%');
        $date_from->setSize('65%');
        $date_to->setSize('65%');

        $pesquisa->setUseButton();
        $options = ['data_cadastro' => 'Data do Cadastro', 'data_evento' => 'Data do Evento', 'created_at' => 'Data de Criação'];
        $pesquisa->addItems($options);
        $pesquisa->setLayout('horizontal');

        $this->form->addAction('Gerar', new TAction(array($this, 'onGenerate')), 'fa:download blue');

        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';

        parent::add($this->form);

        parent::add($table);
    }

    public function onGenerate()
    {
        try {
            $data = $this->form->getData();

            $this->form->validate();

            $date_from = $data->date_from;
            $date_to = $data->date_to;
            $pesquisa = $data->pesquisa;
            $tipo_sinistro = $data->tipo_sinistro;

            $this->form->setData($data);

            $source = TTransaction::open('defciv');

            $query = "  SELECT sinistro_id,
                    sinistro.descricao     as sinistro_descricao
                    ,count(*)               as QTDE
                    ,sum( 
                        case status 
                            when 'B' then 1 
                            when 'A' then 0 
                        end
                    ) as BAIXADAS, 
                    sum( 
                        case status 
                            when 'B' then 0 
                            when 'A' then 1 
                        end 
                    ) as ABERTAS 
            FROM            defciv.ocorrencia       as ocorrencia 
            left join       defciv.sinistro         as sinistro      on sinistro_id = sinistro.id 
            where ocorrencia.{$pesquisa} >= '{$date_from}' and ocorrencia.{$pesquisa} <= '{$date_to}' and sinistro.id = {$tipo_sinistro}
            group by         sinistro_id
                    ,sinistro_descricao 
            order by        sinistro_descricao";

            $rows = TDatabase::getData($source, $query, null, null);

            $date_from_formatado = date('d/m/Y', strtotime($date_from));
            $date_to_formatado = date('d/m/Y', strtotime($date_to));
            $data = date('d/m/Y   h:i:s');

            echo ($tipo_sinistro);

            $content = '<html>
            <head> 
                <title>Ocorrencias</title>
                <link href="app/resources/sinistro_tipo_acao.css" rel="stylesheet" type="text/css" media="screen"/>
            </head>
            <footer></footer>
            <body>
               <div class="header">
               <table class="cabecalho" style="width:100%">
                    <tr>
                        <td colspan="2">
                        <img src="app/images/logo_prefeitura.jpeg" class="logo_a_esquerda">
                        <img src="app/images/logo_defesa_civil.png" class="logo_a_direita">
                <div style="text-align: center;">
                        ESTADO DE SANTA CATARINA<br>
                        MUNICÍPIO DE SANTA CATARINA<br>
                        GABINETE DO PREFEITO<br>
                        DIRETORIA DE PROTEÇÃO E DEFESA CIVIL
                </div>
                        </td>
                    </tr>
                </table>        
                </div>

                <table class="borda_tabela" style="width: 100%">
                    <tr>
                        <td class="borda_inferior_centralizador"><b>Id</b></td> 
                        <td class="borda_inferior"><b>Descrição</b></td>
                        <td class="borda_inferior_centralizador"><b>Quantidade</b></td>
                        <td class="borda_inferior_centralizador"><b>Baixadas</b></td>
                        <td class="borda_inferior_centralizador"><b>Abertas</b></td>
                    </tr>';

            $totalQtde = 0;
            $totalBaixadas = 0;
            $totalAbertas = 0;

            foreach ($rows as $row) {
                $content .= "<tr>
                                <td class='borda_direita'>{$row['sinistro_id']}</td>
                                <td class='direita'>{$row['sinistro_descricao']}</td>
                                <td class='borda_direita_esquerda'>{$row['QTDE']}</td>
                                <td class='borda_direita_esquerda'>{$row['BAIXADAS']}</td>
                                <td class='centralizar'>{$row['ABERTAS']}</td>
                            </tr>";

                $totalQtde += $row['QTDE'];
                $totalBaixadas += $row['BAIXADAS'];
                $totalAbertas += $row['ABERTAS'];
            }

            $content .= "<tr>
                            <td class='espaco_para_direta' colspan=2><b>Total:</b></td>
                            <td class='centralizador_com_borda_esquerda'><b>{$totalQtde}</b></td>
                            <td class='centralizador_com_borda'><b>{$totalBaixadas}</b></td>
                            <td class='centralizador_com_borda'><b>{$totalAbertas}</b></td>    
                        </tr>
                    </table>
                </body>
            </html>";

            // Debug the final HTML content
            file_put_contents('app/output/debug.html', $content);

            // Dompdf setup
            $options = new \Dompdf\Options();
            $options->setChroot(getcwd());
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($content);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents('app/output/document.pdf', $dompdf->output());

            $window = TWindow::create(('Document HTML->PDF'), 0.8, 0.8);
            $object = new TElement('object');
            $object->data = 'app/output/document.pdf';
            $object->type = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="' . $object->data . '"> clique aqui para baixar</a>...');

            $window->add($object);
            $window->show();

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
