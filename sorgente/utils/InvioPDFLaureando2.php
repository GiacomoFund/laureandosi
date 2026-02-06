<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once(realpath(dirname(__FILE__)) . '/ProspettoPDFLaureando2.php');
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\PHPMailer\src\Exception.php');
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\PHPMailer\src\PHPMailer.php');
require_once('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\PHPMailer\src\SMTP.php');
/**
 * @access public
 * @author franc
 */

class InvioPDFLaureando2 {
    /**
     * @AttributeType int[]
     */
    private $_matricole;
    /**
     * @AssociationType ProspettoPDFLaureando2
     */
    private $_cdl;
    private $_dataLaurea;

    /**
     * @access public
     * @param int[] aMatricole
     * @ParamType aMatricole int[]
     */

    public function __construct(){
        $json_content = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario.json');

        $this->_matricole = json_decode($json_content,true);
        $json_content2 = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario2.json');
        $this->_cdl = json_decode($json_content2,true);
        $json_content3 = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\ausiliario3.json');
        $this->_dataLaurea = json_decode($json_content3,true);
    }
    public function invioProspetti(){
        for ($j = 0; $j < sizeof($this->_matricole); $j++) {
            $prospetto = new ProspettoPdfLaureando2($this->_matricole[$j], $this->_cdl, $this->_dataLaurea);
            $this->inviaProspetto( $prospetto->_carrieraLaureando);
        }
    }
    /**
     * @access public
     * @return void
     * @ReturnType void
     */
    public function inviaProspetto($studente_carriera) {

        $messaggio = new PHPMailer();
        //$messaggio->IsSMTP();
        $messaggio->Host = "mixer.unipi.it";
        $messaggio->SMTPSecure = "tls";
        $messaggio->SMTPAuth = false;
        $messaggio->Port = 25;

        $messaggio->From='no-reply-laureandosi@ing.unipi.it';
        $messaggio->AddAddress($studente_carriera->_email);
        $messaggio->Subject='Appello di laurea in Ing. TEST- indicatori per voto di laurea';
        $messaggio->Body=stripslashes('Gentile laureando/laureanda,
		Allego un prospetto contenente: la sua carriera, gli indicatori e la formula che la commissione adopererà per determinare il voto di laurea.
		La prego di prendere visione dei dati relativi agli esami.
		In caso di dubbi scrivere a: ...
		
		Alcune spiegazioni:
		- gli esami che non hanno un voto in trentesimi, hanno voto nominale zero al posto di giudizio o idoneita\', in quanto non contribuiscono al calcolo della media ma solo al numero di crediti curriculari;
		- gli esami che non fanno media (pur contribuendo ai crediti curriculari) non hanno la spunta nella colonna MED;
		- il voto di tesi (T) appare nominalmente a zero in quanto verra\' determinato in sede di laurea, e va da 18 a 30.
		
		 Cordiali saluti
		 Unità Didattica DII');


        $messaggio->AddAttachment("data\pdf_generati\\" . $studente_carriera->_matricola . "-prospetto.pdf");

        if(!$messaggio->Send()){
            echo $messaggio->ErrorInfo;
            echo "Errore nell invio<br>";
        }else{
            echo "Email inviata correttamente!<br>";
        }

        //$messaggio->SmtpClose();
        unset($messaggio);
    }
}
?>