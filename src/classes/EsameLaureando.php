<?php
/**
 * Classe Entity: EsameLaureando
 * Rappresenta un singolo esame sostenuto dal laureando.
 */
class EsameLaureando {

    private $nome;
    private $codice;
    private $voto;
    private $cfu;
    private $data;
    private $faMedia;
    private $informatico;

    /**
     * Costruttore di EsameLaureando.
     *
     * @param string  $nome        Nome dell'esame
     * @param string  $codice      Codice dell'esame
     * @param int     $voto        Voto conseguito
     * @param int     $cfu         Crediti formativi
     * @param string  $data        Data di superamento
     * @param bool    $faMedia     Se l'esame concorre alla media
     * @param bool    $informatico Se l'esame e' informatico
     */
    public function __construct($nome, $codice, $voto, $cfu, $data, $faMedia = true, $informatico = false) {
        $this->nome = $nome;
        $this->codice = $codice;
        $this->voto = $voto;
        $this->cfu = $cfu;
        $this->data = $data;
        $this->faMedia = (bool) $faMedia;
        $this->informatico = (bool) $informatico;
    }

    // ---- Getters ----

    public function getNome() {
        return $this->nome;
    }

    public function getCodice() {
        return $this->codice;
    }

    public function getVoto() {
        return $this->voto;
    }

    public function getCfu() {
        return $this->cfu;
    }

    public function getData() {
        return $this->data;
    }

    public function getFaMedia() {
        return $this->faMedia;
    }

    public function getInformatico() {
        return $this->informatico;
    }

    // ---- Setters ----

    public function setFaMedia($faMedia) {
        $this->faMedia = (bool) $faMedia;
    }

    public function setInformatico($informatico) {
        $this->informatico = (bool) $informatico;
    }

    public function setVoto($voto) {
        $this->voto = $voto;
    }
}
