<?php
class ModificaParametriCofigurazione{
    private $corso_di_laurea;
    private $esami_inf = array();
    public function __construct($cdl_in, $informatici){
        $this->corso_di_laurea = $cdl_in;
        $this->esami_inf = $informatici;
    }
    public function modificaFormula($new_formula){
        $val = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\formule_laurea.json');
        $val1 = json_decode($val,true);
        $val1[$this->corso_di_laurea]["formula"] = $new_formula;
        $new_json = json_encode($val1,JSON_PRETTY_PRINT);
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\formule_laurea.json',$new_json);

        $var = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\formule_laurea.json');
        $var1 = json_decode($var,true);
        $var1[$this->corso_di_laurea]["formula"] = $new_formula;
        $new_json1 = json_encode($var1,JSON_PRETTY_PRINT);
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\formule_laurea.json',$new_json1);
    }
    public function modificaEsamiInformatici(){
        $val = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\esami_informatici.json');
        $val1 = json_decode($val,true);
        $val1["nomi_esami"] = $this->esami_inf;
        $new_json = json_encode($val1,JSON_PRETTY_PRINT);
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\utils\json_files\esami_informatici.json',$new_json);

        $var = file_get_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\esami_informatici.json');
        $var1 = json_decode($var,true);
        $var1["nomi_esami"] = $this->esami_inf;
        $new_json1 = json_encode($var1,JSON_PRETTY_PRINT);
        file_put_contents('C:\Users\franc\Local Sites\genera-prospetti-laurea\app\public\data\esami_informatici.json',$new_json1);
    }
}
?>
