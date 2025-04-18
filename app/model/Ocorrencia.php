
<?php

use Adianti\Database\TRecord;

/**
 * @author Lucas Bortoloti <bortoloti91@gmail.com
 */
class Ocorrencia extends TRecord
{
    const TABLENAME = 'ocorrencia';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {

        parent::__construct($id, $callObjectLoad);

        parent::addAttribute('id');
        parent::addAttribute('sinistro_id');
        parent::addAttribute('data_cadastro');
        parent::addAttribute('status');
        parent::addAttribute('bairro_id');
        parent::addAttribute('logradouro_id');
        parent::addAttribute('OCO_TIPOACAO');
    }
}
