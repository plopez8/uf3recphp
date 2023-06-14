<?php
class DB
{
    private static $instance = null;
    private ?PDO $dbh;
    private bool $connected = false;

    /**
     * Constructor privat (Singelton)
     */
    private function __construct()
    {
        try {
            error_log("try");
            $username = 'dwes-user';
            $password = 'dwes-pass';
            $this->dbh = new PDO('mysql:host=localhost;dbname=eleccions', $username, $password);
            $this->connected = true;
        } catch (Exception $e) {
            error_log("catch");
            error_log($e->getMessage());
        }
    }

    /**
     * Mètode per agafar la instància sempre activa (Singelton)
     * @return DB
     */
    public static function get_instance(): DB
    {
        if (self::$instance == null) {
            self::$instance = new DB();
        }

        return self::$instance;
    }

    /**
     * Comprova la connexió amb la base de dades.
     * @return bool
     */
    public function connected() : bool
    {
        return $this->connected;
    }

    /**
     * Retorna un array amb les comarques
     * @return array
     */
    public function get_comarques(): array
    {
        if(!$this->connected) return [];

        $stmt = $this->dbh->prepare("SELECT nom FROM comarques");
        $success = $stmt->execute();
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return [];

        // Converteix a array pla
        $comarques = [];
        foreach ($arr as $row){
            $comarques[] = $row["nom"];
        }

        return $comarques;
    }

    /**
     * Retorna un array amb tots els muncipis on cada element és un array (població, comarca i demarcació)
     * @return array
     */
    public function get_municipis(): array
    {
        if(!$this->connected) return [];

        $stmt = $this->dbh->prepare("SELECT poblacio, comarca, demarcacio FROM poblacions");
        $success = $stmt->execute();
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return [];

        return $arr;

    }

    /**
     * Retorna un array amb les demarcacions
     * @return array
     */
    public function get_demarcacions(): array
    {
        if(!$this->connected) return [];
    
        $stmt = $this->dbh->prepare("SELECT nom FROM demarcacions");
        $success = $stmt->execute();
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
    
        if (!$success) {
            return [];
        }
    
        $demarcacions = [];
        foreach ($arr as $row){
            $demarcacions[] = $row["nom"];
        }

        return $demarcacions;
    }

    /**
     * Retorna un array tots els partits, cada element és un array (nom, color i curt)
     * On curt és el nom abreviat del partit
     * @return array
     */
    public function get_all_partits(): array
    {
        if(!$this->connected) return [];

        $stmt = $this->dbh->prepare("SELECT nom, color, curt FROM partits");
        $success = $stmt->execute();
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return [];

        return $arr;
    }

    /**
     * Retorna tots els partits amb candidatures a una demarcació, cada element és un array (nom, color i curt)
     * On curt és el nom abreviat del partit
     * @param $demarcacio
     * @return array
     */
    public function get_partits($demarcacio): array
    {
        if(!$this->connected) return [];
        try {
            $stmt = $this->dbh->prepare(
                "SELECT DISTINCT p.nom, p.color, p.curt FROM partits p
                INNER JOIN candidatures c ON p.curt = c.partit
                INNER JOIN poblacions po ON c.demarcacio = po.demarcacio
                WHERE UPPER(po.demarcacio) = UPPER(?);"
            );
            $success = $stmt->execute([$demarcacio]);
            $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = null;

            if(!$success)
                return [];

            return $arr;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    /**
     * Comprova si existeix una demarcació donada
     * @param $demarcacio
     * @return bool
     */
    public function find_demarcacio($demarcacio): bool
    {
        if(!$this->connected) return false;

        $stmt = $this->dbh->prepare("SELECT nom FROM demarcacions WHERE UPPER(nom) =UPPER(?);");
        $success = $stmt->execute([$demarcacio]);
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return false;

        return true; // return $trobat;
    }

    /**
     * Retorna el nombre d'escons destinats a una demarcació
     * @param string $demarcacio
     * @return int
     */
    public function get_num_escons(string $demarcacio): int
    {
        if(!$this->connected) return 0;

        $stmt = $this->dbh->prepare(
            "SELECT escons FROM demarcacions WHERE UPPER(nom)=UPPER(?);"
        );
        $success = $stmt->execute([$demarcacio]);
        $arr = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success || count($arr) < 1)
            return 0;

        return $arr["escons"];
    }

    /**
     * Retorna una llista dels vots de cada partit donada una demarcació
     * Les claus de l'array de sortida són els partits i els valors els vots
     *
     * @param string $demarcacio
     * @return array
     */
    public function get_vots(string $demarcacio) : array
    {
        if(!$this->connected) return [];
        $stmt = $this->dbh->prepare(
            "SELECT DISTINCT v.partit, v.vots FROM vots v
            INNER JOIN poblacions p ON v.poblacio = p.poblacio
            WHERE UPPER(p.demarcacio) = UPPER(?);"
        );
        $success = $stmt->execute([$demarcacio]);
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return [];

        $vots = [];
        foreach ($arr as $row){
            $vots[$row["partit"]] = $row["vots"];
        }

        return $vots;
    }

    /**
     * Retorna una llista dels escons de cada partit donada una demarcació
     * Les claus de l'array de sortida són els partits i els valors els escons
     *
     * @param string $demarcacio
     * @return array
     */
    public function get_escons(string $demarcacio) : array
    {
        if(!$this->connected) return [];

        $stmt = $this->dbh->prepare(
            "SELECT partit, escons FROM escons
            WHERE UPPER(demarcacio) = UPPER(?)"
        );
        $success = $stmt->execute([$demarcacio]);
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return [];

        // Converteix al format [partits (clau), escons(valors)]
        $vots = [];
        foreach ($arr as $row){
            $vots[$row["partit"]] = $row["escons"];
        }

        return $vots;
    }

    /**
     * Retorna una llista dels escons aconseguits per cada partit
     * Les claus de l'array de sortida són els partits i els valors els escons
     *
     * @return array
     */
    public function get_all_escons() : array
    {
        if(!$this->connected) return [];

        $stmt = $this->dbh->prepare("SELECT partit, SUM(escons) as total_escons FROM escons GROUP BY partit");
        $success = $stmt->execute();
        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
    
        if(!$success)
            return [];
    
        $escons = [];
        foreach ($arr as $row){
            $partit = $row["partit"];
            $escons = $row["total_escons"];
            if(isset($escons[$partit])){
                $escons[$partit] += $total_escons;
            } else {
                $escons[$partit] = $total_escons;
            }
        }
    
        return $escons;
    }

    /**
     * Retorna el nombre de demarcacions que ja tenen escons assignats
     *
     * @return int
     */
    public function count_demarcacio_with_escons() : int
    {
        if(!$this->connected) return false;

        $stmt = $this->dbh->prepare("SELECT COUNT(DISTINCT demarcacio) AS num_demarcacions FROM escons");
        $success = $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null;

        if(!$success)
            return 0;

        return $result['num_demarcacions'];
    }

    /**
     * Assigna un array de partits (clau) i escons (valor) a una demarcació
     *
     * @param string $demarcacio
     * @param array $assignacio_escons
     * @return bool
     */
    public function set_escons(string $demarcacio, array $assignacio_escons) : bool
    {
        if(!$this->connected) return false;

        try {
            $this->dbh->beginTransaction();
    
            $stmtDelete = $this->dbh->prepare("DELETE FROM escons WHERE UPPER(demarcacio) = UPPER(?)");
            $stmtDelete->execute([$demarcacio]);
    
            $stmtInsert = $this->dbh->prepare("INSERT INTO escons (partit, demarcacio, escons) VALUES (?, ?, ?)");
            foreach ($assignacio_escons as $assignacio) {
                $partit = $assignacio['partit'];
                $numEscons = $assignacio['escons'];
                $stmtInsert->execute([$partit, $demarcacio, $numEscons]);
            }
    
            $this->dbh->commit();
    
            return true;
        } catch (PDOException $e) {
            $this->dbh->rollBack();
            return false;
        }
    }

    /**
     * Assigna un array de partits (clau) i vots (valor) a una població
     *
     * @param string $poblacio
     * @param array $vots_partits
     * @return bool
     */
    public function set_vots(string $poblacio, array $vots_partits) : bool
    {
        if(!$this->connected) return false;

        try {
            $this->dbh->beginTransaction();
    
            $stmtDelete = $this->dbh->prepare("DELETE FROM vots WHERE UPPER(poblacio) = UPPER(?)");
            $stmtDelete->execute([$poblacio]);
    
            $stmtInsert = $this->dbh->prepare("INSERT INTO vots (partit, poblacio, vots) VALUES (?, ?, ?)");
            foreach ($vots_partits as $partit => $vots) {
                $partit = $partit;
                $votos = $vots;
                $stmtInsert->execute([$partit, $poblacio, $votos]);
            }
    
            $this->dbh->commit();
    
            return true;
        } catch (PDOException $e) {
            $this->dbh->rollBack();
            return false;
        }    
    }
}
