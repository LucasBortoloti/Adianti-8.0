<?php

use Adianti\Control\TPage;

/**
 * JogosForm Registration
 * @author Lucas Bortoloti <bortoloti91@gmail.com
 */
class JogosForm extends TPage
{
    protected $form; // form

    use Adianti\Base\AdiantiStandardFormTrait; // Standard form methods

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {

        parent::__construct();


        $this->setDatabase('jogos');              // defines the database
        $this->setActiveRecord('Jogos');     // defines the active record

        // creates the form
        $this->form = new BootstrapFormBuilder('form_Jogos');
        $this->form->setFormTitle('Jogos');


        // create the form fields 
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $ano_lancamento = new TEntry('ano_lancamento');
        $quantidade_avaliacoes = new TEntry('quantidade_avaliacoes');
        $desenvolvedoras_id = new TDBCombo('desenvolvedoras_id', 'jogos', 'Desenvolvedoras', 'id', 'nome');
        $thumbnail = new TEntry('thumbnail');
        $sinopse = new TEntry('sinopse');
        $avaliacoes = new TEntry('avaliacoes');
        $vendas = new TEntry('vendas');

        // add the fields 
        $this->form->addFields([new TLabel('id')], [$id]);
        $this->form->addFields([new TLabel('nome')], [$nome]);
        $this->form->addFields([new TLabel('ano_lancamento')], [$ano_lancamento]);
        $this->form->addFields([new TLabel('quantidade_avaliacoes')], [$quantidade_avaliacoes]);
        $this->form->addFields([new TLabel('desenvolvedoras_id')], [$desenvolvedoras_id]);
        $this->form->addFields([new TLabel('thumbnail')], [$thumbnail]);
        $this->form->addFields([new TLabel('sinopse')], [$sinopse]);
        $this->form->addFields([new TLabel('avaliacoes')], [$avaliacoes]);
        $this->form->addFields([new TLabel('vendas')], [$vendas]);

        if (!empty($id)) {
            $id->setEditable(FALSE);
        }

        /** samples
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( '100%' ); // set size
         **/

        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'),  new TAction([$this, 'onEdit']), 'fa:eraser red');

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);


        parent::add($container);
    }
}
