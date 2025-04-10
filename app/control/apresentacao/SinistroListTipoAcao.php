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

class SinistroListTipoAcao extends TPage
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
        $this->form->setFormTitle(('Sinistros, baixados e abertos por tipo de ação'));

        // $id = new TEntry('id');
        $date_from = new TDate('date_from');
        $date_to = new TDate('date_to');

        $tipoacao = new TRadioGroup('tipoacao');

        $sinistro_id = new TDBUniqueSearch('sinistro_id', 'defciv', 'Sinistro', 'id', 'descricao');
        $sinistro_id->setMinLength(1);
        $sinistro_id->setMask('{descricao} ({id})');
        $pesquisa = new TRadioGroup('pesquisa');

        $this->form->addFields([new TLabel('De')], [$date_from]);
        $this->form->addFields([new TLabel('Até')], [$date_to]);
        $this->form->addFields([new TLabel('Tipo Ação')], [$tipoacao]);
        $this->form->addFields([new TLabel('Tipo de pesquisa')], [$pesquisa]);


        $date_from->addValidation('De', new TRequiredValidator);
        $date_to->addValidation('Até', new TRequiredValidator);
        $tipoacao->addValidation('Tipo de Ação', new TRequiredValidator);
        $pesquisa->addValidation('Pesquisa', new TRequiredValidator);

        $date_from->setSize('65%');
        $date_to->setSize('65%');

        $pesquisa->setUseButton();
        $options = ['data_cadastro' => 'Data do Cadastro', 'data_evento' => 'Data do Evento', 'created_at' => 'Data de Criação'];
        $pesquisa->addItems($options);
        $pesquisa->setLayout('horizontal');

        $tipoacao->setUseButton();
        $options = ['P' => 'P', 'R' => 'R'];
        $tipoacao->addItems($options);
        $tipoacao->setLayout('horizontal');

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
            $tipoacao = $data->tipoacao;
            $pesquisa = $data->pesquisa;

            $this->form->setData($data);

            $source = TTransaction::open('defciv');

            $query = "  SELECT sinistro_id,
                    sinistro.descricao     as sinistro_descricao,
                    OCO_TIPOACAO as tipo_acao
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
            where ocorrencia.{$pesquisa} >= '{$date_from}' and ocorrencia.{$pesquisa} <= '{$date_to}' and ocorrencia.OCO_TIPOACAO = '{$tipoacao}'
            group by         sinistro_id
                    ,sinistro_descricao 
            order by        sinistro_descricao";

            $rows = TDatabase::getData($source, $query, null, null);

            $date_from_formatado = date('d/m/Y', strtotime($date_from));
            $date_to_formatado = date('d/m/Y', strtotime($date_to));
            $data = date('d/m/Y   h:i:s');

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
                            <td><b><i>PREFEITURA MUNICIPAL DE JARAGUÁ DO SUL</i></b></td>
                        </tr>
                        <tr>
                            <td> prefeitura@jaraguadosul.com.br</td>
                        </tr>
                        <tr>
                            <td>83.102.459/0001-23</td>
                            <td class="data_hora"><b>' . $data . '</b></td>
                        </tr>
                        <tr>
                            <td>(047) 2106-8000</td>
                            <td class="cor_ocorrencia colspan=4">Ocorrência de ' . $date_from_formatado . ' até ' . $date_to_formatado . ' por tipo de ação ' . $tipoacao . '</td>                  
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
