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

class SinistroListParecer extends TPage
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
        $this->form->setFormTitle(('Pareceres Emitidos'));

        // $id = new TEntry('id');
        $date_from = new TDate('date_from');
        $date_to = new TDate('date_to');

        $this->form->addFields([new TLabel('De')], [$date_from]);
        $this->form->addFields([new TLabel('Até')], [$date_to]);

        $date_from->addValidation('De', new TRequiredValidator);
        $date_to->addValidation('Até', new TRequiredValidator);

        $date_from->setSize('65%');
        $date_to->setSize('65%');

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

            $this->form->setData($data);

            $source = TTransaction::open('defciv');

            $query = " SELECT o.id as ocorrencia_id,
					o.solicitante as solicitante,
					o.logradouro_id as logradouro_num,
					o.bairro_id as bairro_id,
					o.interdicao_lote_num as lote_interdicao,
					o.OCO_PARECER as parecer,
					o.OCO_PARECERDATA as parecer_data,
            		o.status as status,
            		o.sinistro_id as sinistro_id,
            		s.descricao as sinistro_descricao,
            		l.nome as logradouro_nome,
            		b.nome as bairro_nome
                    	from defciv.ocorrencia o
                   left join defciv.sinistro s on s.id = o.sinistro_id
                   left join vigepi.bairro b on b.id = o.bairro_id
                   left join vigepi.logradouro l on l.id = o.logradouro_id
               where o.OCO_PARECERDATA >= '{$date_from}' and o.OCO_PARECERDATA <= '{$date_to}'
                   group by solicitante, parecer_data
				   order by parecer_data, solicitante";

            $rows = TDatabase::getData($source, $query, null, null);

            $date_from_formatado = date('d/m/Y', strtotime($date_from));
            $date_to_formatado = date('d/m/Y', strtotime($date_to));
            $data = date('d/m/Y   h:i:s');

            $content = '<html>
            <head> 
                <title>Ocorrencias</title>
                <link href="app/resources/sinistro_parecer.css" rel="stylesheet" type="text/css" media="screen"/>
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
                        <td class="borda_inferior_centralizador"><b>Ocorrencia Id</b></td> 
                        <td class="borda_inferior"><b>Solicitante</b></td>
                        <td class="borda_inferior_centralizador"><b>Logradouro Num</b></td>
                        <td class="borda_inferior_centralizador"><b>Bairro Id</b></td>
                        <td class="borda_inferior_centralizador"><b>Interdição Lote</b></td>
                        <td class="borda_inferior_centralizador"><b>Parecer</b></td>
                        <td class="borda_inferior_centralizador"><b>Parecer Data</b></td>
                        <td class="borda_inferior_centralizador"><b>Status</b></td>
                        <td class="borda_inferior_centralizador"><b>Sinistro Id</b></td>
                        <td class="borda_inferior_centralizador"><b>Sinistro Descrição</b></td>
                        <td class="borda_inferior_centralizador"><b>Logradouro</b></td>
                        <td class="borda_inferior_centralizador"><b>Bairro</b></td>
                    </tr>';

            foreach ($rows as $row) {
                $content .= "<tr>
                                <td class='borda_direita'>{$row['ocorrencia_id']}</td>
                                <td class='direita'>{$row['solicitante']}</td>
                                <td class='direita'>{$row['logradouro_num']}</td>
                                <td class='direita'>{$row['bairro_id']}</td>
                                <td class='direita'>{$row['lote_interdicao']}</td>
                                <td class='direita'>{$row['parecer']}</td>
                                <td class='direita'>{$row['parecer_data']}</td>
                                <td class='direita'>{$row['status']}</td>
                                <td class='direita'>{$row['sinistro_id']}</td>
                                <td class='direita'>{$row['sinistro_descricao']}</td>
                                <td class='direita'>{$row['logradouro_nome']}</td>
                                <td class='direita'>{$row['bairro_nome']}</td>
                            </tr>";
            }

            $content .= "</table>
                </body>
            </html>";

            // Debug the final HTML content
            file_put_contents('app/output/debug.html', $content);

            // Dompdf setup
            $options = new \Dompdf\Options();
            $options->setChroot(getcwd());
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($content);
            $dompdf->setPaper('A4', 'landscape');
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
