<?
    final class Cookie{
        /**
         * @var array Engedélyezett módok a dellMoreByMatch fügvényhez.
         */
        private static $allowed_dell_match_modes = array('E','S');
        /////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////
        /**
         * Eltárol egy sütit a szerveren.
         *
         * @param string $key Süti azonosító.
         * @param mixed $value Süti érték.
         * @param int $time Érvényességi ideje: time() + 60*60*24 = 1 nap
         * @param string $path
         *
         * @return void
         */
        final static public  function set($key, $value, $time = 0, $path = '/'){
            setcookie($key,$value,$time,$path);
        }

        /**
         * Egy süti törlése kulcsazonosító alapján.
         *
         * @param string $key Süti kulcsazonosító.
         * @param string $path Süti relatív elérés.
         *
         * @return void
         */
        final static public function dellByKey($key, $path = '/'){
            setcookie($key,'', time() - 10, $path);
        }

        /**
         * @param string $mode Keresési mód: E=exists|S=start
         * @param string $key Keresési szöveg
         * @param string $path Path
         * @return bool TRUE, ha lefutott és törlődtek a sütik, de ha FALSE, akkor nem engedélyezett $mode lett kiválasztva vagy nem lett megadva $key.
         */
        final static public function dellMoreByMatch($mode, $key = '', $path = '/'){
            if(!in_array($mode,self::$allowed_dell_match_modes)) return false;
            if($key == '') return false;

            foreach($_COOKIE as $ck => $cv){
                switch($mode){
                    // Exists match
                    case 'E':
                            if(strpos($ck,$key) !== false){
                                self::dellByKey($ck,$path);
                            }
                        break;
                    // Start
                    case 'S':
                        if(preg_match('/^'.$key.'*/i',$ck)){
                            self::dellByKey($ck,$path);
                            //echo $ck.' => '.$cv.'<br>';
                        }
                        break;
                }
            }

            return true;
        }

        /**
         * Ellenőrzés, hogy egy adott kulcsérték alapján léteznek-e sütik a böngészőben.
         *
         * @param string $mode Keresési mód: E=exists|S=start
         * @param string $keystring Keresési szöveg
         * @param string $path
         *
         * @return bool TRUE ha létezik FALSE, ha nem
         */
        final static public function checkKeyExistsByMatch($mode, $keystring = '', $path = '/'){
            $bool = false;

            if(!in_array($mode,self::$allowed_dell_match_modes)) return false;
            if($keystring == '') return false;

            foreach($_COOKIE as $ck => $cv){
                switch($mode){
                    // Exists match
                    case 'E':
                        if(strpos($ck,$keystring) !== false){
                           $bool = true;
                        }
                        break;
                    // Start
                    case 'S':
                        if(preg_match('/^'.$keystring.'*/i',$ck)){
                           $bool = true;
                        }
                        break;
                }
            }

            return $bool;
        }

        final static public function getByMatch($mode, $keystring = '', $path = '/'){
            $bool = false;

            if(!in_array($mode,self::$allowed_dell_match_modes)) return false;
            if($keystring == '') return false;

            $back = array();

            foreach($_COOKIE as $ck => $cv){
                switch($mode){
                    // Exists match
                    case 'E':
                        if(strpos($ck,$keystring) !== false){
                            $uk = str_replace($keystring.'_','',$ck);
                            $back[$uk] = $cv;
                        }
                        break;
                    // Start
                    case 'S':
                        if(preg_match('/^'.$keystring.'*/i',$ck)){
                           $uk = str_replace($keystring.'_','',$ck);
                           $back[$uk] = $cv;
                        }
                        break;
                }
            }

            return $back;
        }

        final static public function getStoredKeysByJS($cookie_key){
            $ret = array();
                $cookie = $_COOKIE[$cookie_key];
                if(isset($cookie) && $cookie != ''){
                    $exp = explode(",",$cookie);
                    foreach($exp as $e){
                        $ret[] = trim($e);
                    }
                }
            return $ret;
        }
    }
?>