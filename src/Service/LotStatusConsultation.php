<?php namespace NFse\Service;

use Exception;
use NFse\Models\Settings;

use NFse\Service\ConsultBase;
use NFse\Soap\ConsultaSituacaoLoteRps;
use NFse\Soap\ErrorMsg;
use NFse\Soap\Soap;
use NFse\Helpers\XML;

class LotStatusConsultation extends ConsultBase
{
    private $xSoap;
    private $setting;

    /**
     * constroi o xml de consulta
     *
     * @param NFse\Models\Settings;
     * @param string número de protocolo
     */
    public function __construct(Settings $settings, string $numProtocol)
    {
        $this->xSoap = new Soap($settings, ($settings->issuer->codMun != 3147105 ? 'ConsultarSituacaoLoteRpsRequest' : 'ConsultarLoteRps'));
        $this->setting = $settings;
        $parameters = (object) [
            'numProtocol' => $numProtocol,
            'file' => $settings->issuer->codMun != 3147105 ? 'consultaSituacaoLoteRps' : 'consultarNfseRpsEnvio',
            'prestador' => $settings->issuer->cnpj
        ];

        parent::__construct();
        $this->syncModel = $this->callConsultation($settings, $parameters);
    }

    public function setQuasarTag (array $parameters)
    {
        $this->xml = XML::load('consultarNfseRpsEnvio')
        ->set('Rps', $parameters['numRPS'])
        ->set('Cnpj', $parameters['cnpj'])
        ->set('InscricaoMunicipal', $parameters['inscricaoMunicipal'])
        ->set('Senha', $parameters['senha'])
        ->set('FraseSecreta', $parameters['fraseSecreta'])
        ->filter()
        ->save();
    }

    /**
     * envia a consulta para o servidor da PBH
     */
    public function sendConsultation(): object
    {
        if($this->setting->issuer->codMun == 3147105){
            $format = '<?xml version="1.0" encoding="UTF-8"?>' . $this->xml;
            $order = ["\r\n", "\n", "\r", "\t"];
            $result = str_replace($order, '', htmlspecialchars($format, ENT_QUOTES | ENT_XML1));
        }else{
            $result = $this->getXML();
        }

        //envia a chamada para o SOAP
        try {
            $this->xSoap->setXML($result);
            $wsResponse = $this->xSoap->__soapCall();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        //carrega o xml de resposta para um object
        $xmlResponse = isset($wsResponse->outputXML) ? simplexml_load_string($wsResponse->outputXML) : simplexml_load_string($wsResponse->return);
        //identifica o retorno e faz o processamento nescessário
        if (is_object($xmlResponse) && isset($xmlResponse->ListaMensagemRetorno)) {
            $wsError = new ErrorMsg($xmlResponse);
            $messages = $wsError->getMessages();

            return (object) $this->errors = ($messages) ? $messages : $wsError->getError();
        } else {
            $wsLote = new ConsultaSituacaoLoteRps($xmlResponse, $this->setting);
            return (object) $wsLote->getDadosLote();
        }
    }
}
