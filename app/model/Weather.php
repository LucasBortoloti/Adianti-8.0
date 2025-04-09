<?php

use Adianti\Database\TRecord;

class Weather extends TRecord
{
    const TABLENAME = 'clima';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);

        parent::addAttribute('id');
        parent::addAttribute('city');
        parent::addAttribute('region');
        parent::addAttribute('temperature');
        parent::addAttribute('condition');
        parent::addAttribute('humidity');
        parent::addAttribute('wind_speed');
        parent::addAttribute('observation_time');
        parent::addAttribute('created_at');
        parent::addAttribute('icon');
    }
}
