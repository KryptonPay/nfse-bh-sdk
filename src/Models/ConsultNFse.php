<?php namespace NFse\Models;

class ConsultNFse
{
    /**
     *@var string data inicial da pequisa
     */
    public $startDate;

    /**
     *@var string data final da pequisa
     */
    public $endDate;

    /**
     *@var int tipo tomador
     *
     * 1 - CNPJ | 2 - CPF
     */
    public $takerType;

    /**
     *@var string documento do tomador
     *
     */
    public $document;

    /**
     *@var string documento do tomador
     *
     */
    public $secret_phrase;

    /**
     *@var string documento do tomador
     *
     */
    public $password;

    /**
     *@var string documento do tomador
     *
     */
    public $subscriber_provider;
}
