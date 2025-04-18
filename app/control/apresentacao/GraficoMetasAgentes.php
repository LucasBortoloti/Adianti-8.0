<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class GraficoMetasAgentes extends TPage
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

        $this->setDatabase('vigepi');          // defines the database
        $this->setActiveRecord('MetaAgentesView');         // defines the active record
        $this->setDefaultOrder('id', 'asc');    // defines the default order
        $this->addFilterField('id', '=', 'id'); // filterField, operator, formField

        $this->addFilterField('date', '>=', 'date_from', function ($value) {
            return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        });

        $this->addFilterField('date', '<=', 'date_to', function ($value) {
            return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        });

        $this->form = new BootstrapFormBuilder('form_search_Atividade');
        $this->form->setFormTitle(('Gráfico Agentes'));

        // $id = new TEntry('id');
        $data_inicial = new TDate('data_inicial');
        $data_final = new TDate('data_final');

        $atividade_id = new TDBCombo('atividade_id', 'vigepi', 'MetaAgentesView', 'id', 'id');
        $servidor_id = new TDBUniqueSearch('servidor_id', 'vigepi', 'MetaAgentesView', 'agente_id', 'agente_nome');
        $pesquisa = new TRadioGroup('pesquisa');
        $output_type  = new TRadioGroup('output_type');

        // $this->form->addFields([new TLabel('De')], [$data_inicial]);
        $this->form->addFields([new TLabel('De')], [$data_inicial]);
        $this->form->addFields([new TLabel('Até')], [$data_final]);
        $this->form->addFields([new TLabel('Servidor')], [$servidor_id]);
        //$this->form->addFields([new TLabel('Id')], [$id]);

        $pesquisa->setUseButton();
        $pesquisa->setLayout('horizontal');

        // $date_from->setMask('dd/mm/yyyy');
        // $date_to->setMask('dd/mm/yyyy');

        $this->form->addAction('Gerar', new TAction(array($this, 'onGenerate')), 'fa:download blue');

        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';

        parent::add($this->form);

        parent::add($table);
    }

    function onGenerate()
    {
        $html = new THtmlRenderer('app/resources/google_column_chart.html');

        $data = $this->form->getData();
        $data_inicial = $data->data_inicial;
        $data_final = $data->data_final;
        $servidor_id = $data->servidor_id;

        // echo "<pre>";
        // print_r($data);
        // echo "<pre>";

        $this->form->setData($data);

        TTransaction::open('vigepi');

        // Construção do critério de filtro
        $criteria = new TCriteria();
        if ($data_inicial) {
            $criteria->add(new TFilter('dia', '>=', TDate::convertToMask($data_inicial, 'dd/mm/yyyy', 'yyyy-mm-dd')));
        }
        if ($data_final) {
            $criteria->add(new TFilter('dia', '<=', TDate::convertToMask($data_final, 'dd/mm/yyyy', 'yyyy-mm-dd')));
        }
        if ($servidor_id) {
            $criteria->add(new TFilter('agente_id', '=', $servidor_id));
        }

        $metas = MetaAgentesView::getObjects($criteria);

        $dados = [['Nome', 'Normal/Recuperado', 'Meta']];

        foreach ($metas as $meta) {
            $dados[] = [
                $meta->agente_nome . ' (' . TDate::convertToMask($meta->dia, 'yyyy-mm-dd', 'dd/mm/yyyy') . ')',
                (float)$meta->normal_ou_recuperado,
                (float)$meta->meta_diaria
            ];
        }

        $div = new TElement('div');
        $div->id = 'container';
        $div->style = 'width:1500px;height:1150px';
        $div->add($html);

        $html->enableSection('main', [
            'data' => json_encode($dados),
            'width' => '100%',
            'height' => '1000px',
            'xtitle' => 'Agente',
            'ytitle' => 'Normal/Recuperado',
            'title' => 'Metas dos Agentes'
        ]);

        TTransaction::close();

        parent::add($div);
    }
}
