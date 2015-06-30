<?php
namespace Robot;

use Arena\RobotOrder;

/*
 * Truc à faire :
 * Enregistrer la position de l'ennemie sur le plateau en fonction de la notre
 * vérifier variable positionEnemy (195)
 * Ne pas définir d'objectif en cas de duel (enemie en face)
 * vérifier l'anulation d'un objectif
*/


class Chappie implements RobotInterface
{
    private $name = null; // A ou B (c'est vraiment de la merde comme choix)
    private $life = 10;
    private $nameEnemy = null;
    private $lifeEnemy = 10;
    private $myPosition = null;
    private $positionEnemyLast = null;
    private $positionEnemy = null;
    private $lastDirectionEnemy = null;
    private $goToUp = false;
    private $goToLeft = false;
    private $goToRight = false;
    private $goToDown = false;
    private $direction = null;  // direction par où l'on s'est fait tirer dessus
    private $ordersNb = null;

    // VARIABLE TACTIQUE
    private $objectifDone = false;
    private $objectif = array();
    // X , Y , direction pour shot

    // HISTORIQUE
    private $historiqueEnemy = array();

    public function __construct($name)
    {
        $this->name = $name;

        if ($name == 'A') {
            $this->nameEnemy = 'B';
        } else {
            $this->nameEnemy = 'A';
        }
    }

    public function notifyPosition(\Arena\RobotPosition $position)
    {
        // On enregistre notre position EXACTE !
        $this->myPosition = $position;
        $this->myPosition->x = $position->x + 1;
        $this->myPosition->y = $position->y + 1;

        // On reset la direction
        $this->direction = null;
    }

    public function notifySurroundings($data)
    {
        // $data =
        // ligne
        // colonne
        // colonne
        // colonne
        // ligne
        // colonne
        // ...

        // On reset les directions
        $this->goToUp = false;
        $this->goToLeft = false;
        $this->goToRight = false;
        $this->goToDown = false;

        // On défini l'ancienne position de l'ennemie
        $this->positionEnemyLast = $this->positionEnemy;
        $this->positionEnemy = null;

        /*echo '<pre>';
        var_dump($data);
        echo '</pre>';*/
        // On récupère la nouvelle position de l'ennemie
        foreach ($data as $ligne => $value) {
            $lookObstacleHaut = $lookObstacleMilieu = $lookObstacleBas = false;

            if ($this->myPosition->y == $ligne - 1) {
                $lookObstacleHaut = true;
            } elseif ($this->myPosition->y == $ligne) {
                $lookObstacleMilieu = true;
            } elseif ($this->myPosition->y == $ligne + 1) {
                $lookObstacleBas = true;
            }

            foreach ($value as $colonne => $value2) {
                if ($value2 == $this->nameEnemy) {
                    $this->positionEnemy = array('X' => $this->myPosition->x + ($colonne - 2),
                                                 'Y' => $this->myPosition->y + ($ligne - 2));
                }

                if ($lookObstacleHaut == true) {
                    if ($this->myPosition->x == $ligne && $value2 == '.') {
                        $this->goToUp = true;
                    }
                }

                if ($lookObstacleMilieu == true) {
                    if ($this->myPosition->x == $ligne - 1 && $value2 == '.') {
                        $this->goToLeft = true;
                    }
                    if ($this->myPosition->x == $ligne + 1 && $value2 == '.') {
                        $this->goToRight = true;
                    }
                }

                if ($lookObstacleBas == true) {
                    if ($this->myPosition->x == $ligne && $value2 == '.') {
                        $this->goToDown = true;
                    }
                }
            }
        }
        /*echo '<pre>';
        var_dump($this->historiqueEnemy);
        var_dump($this->positionEnemy);
        echo '</pre>';*/
    }

    public function notifyEnnemy($direction)
    {
        $this->direction = $direction;
        $this->life--;
    }

    public function decide()
    {
        $orders = [RobotOrder::TURN_LEFT,
            RobotOrder::TURN_RIGHT,
            RobotOrder::AHEAD,
            RobotOrder::FIRE];

        // On enregistre la position de l'ennemie
        $this->historiqueEnemy[] = array('X' => $this->positionEnemy['X'], // colonne
            'Y' => $this->positionEnemy['Y'], // ligne
            'direction' => $this->direction);

        // On analyse et défini un objectif à faire
        if(!$this->objectif) {
            // Pour cela, il nous faut au moins 3 positions détecté de l'ennemie
            if (count($this->historiqueEnemy) > 3) {
                foreach ($this->historiqueEnemy as $key => $value) {
                    if ($key == 0) {
                        $historique_LastPosition = $value;
                        $roundstay = 0;
                    } else {
                        if ($historique_LastPosition['Y'] == $value['Y'] && $historique_LastPosition['X'] == $value['X'] && !empty($value['Y']) && !empty($value['X'])) {
                            $roundstay++;
                            if(count($historique_LastPosition) == 2) {
                                $this->lastDirectionEnemy[2];
                            }
                        } else {
                            $roundstay = 0;
                        }

                        $historique_LastPosition = $value;
                    }
                }

                // Si le robot adverse est resté 3 tour sur la meme case
                if ($roundstay >= 2) {
                    // On enregistre les 4 positions où Chappie peut brain l'ennemie pour ensuite tester la position à enregistrer comme objectif (Hors du champs de vue de l'ennemie)
                    // POSITION AU DESSUS DE L'ADVERSAIRE
                    $allObjectifs[] = array('X' => $this->positionEnemy['X'],
                        'Y' => $this->positionEnemy['Y'] - 3,
                        'direction' => 'S');
                    // POSITION AU DESSOUS DE L'ADVERSAIRE
                    $allObjectifs[] = array('X' => $this->positionEnemy['X'],
                        'Y' => $this->positionEnemy['Y'] + 3,
                        'direction' => 'N');
                    // POSITION A GAUCHE DE L'AVDVERSAIRE
                    $allObjectifs[] = array('X' => $this->positionEnemy['X'] - 3,
                        'Y' => $this->positionEnemy['Y'],
                        'direction' => 'E');
                    // POSITION A DROITE DE L'ADVERSAIRE
                    $allObjectifs[] = array('X' => $this->positionEnemy['X'] + 3,
                        'Y' => $this->positionEnemy['Y'],
                        'direction' => 'W');

                    // On vérifie quelles positions sont libre
                    foreach ($allObjectifs as $key => $value) {
                        if ($value['X'] > 1 && $value['X'] < 12 && $value['Y'] > 1 && $value['Y'] < 11) {
                            $objectifLibre[] = $allObjectifs[$key];
                        }
                    }

                    // On prend la plus près et celle où ne tire pas l'ennemie
                    foreach ($objectifLibre as $key => $value) {
                        //
                        if($this->lastDirectionEnemy) {
                            if($value['direction'] != $this->inverseDirection($this->lastDirectionEnemy)) {
                                $this->objectif = $value;
                            }
                        } else {
                            $this->objectif = $value;
                        }
                    }
                }
            }
        } else {
            // On vérifie si l'objectif est terminé
            if($this->myPosition == $this->objectif) {
                if($this->positionEnemy != $this->positionEnemyLast && !empty($this->positionEnemy)) {
                    $this->objectif = null;
                }
            }
        }



        // Si on a un objectif
        if ($this->objectif) {
            if($this->objectif['X'] == $this->myPosition->x && $this->objectif['Y'] == $this->myPosition->y && $this->objectif['direction'] == $this->myPosition->direction) {
                // on tire
                $this->ordersNb = 3;
            } else {
                if($this->myPosition->y != $this->objectif['Y']) {
                    if($this->myPosition->y > $this->objectif['Y']) {
                        var_dump('yolo');
                        switch($this->myPosition->direction) {
                            case 'N':
                                // On avance
                                $this->ordersNb = 2;
                                break;
                            case 'S':
                                // On toune à droite
                                $this->ordersNb = 1;
                                break;
                            case 'W':
                                //on tourne à droite
                                $this->ordersNb = 1;
                                break;
                            case 'E':
                                // On tourne à gauche
                                $this->ordersNb = 0;
                                break;
                        }
                    } else {
                        var_dump('yolo2');
                        switch ($this->myPosition->direction) {
                            case 'N':
                                // On tourne à droite
                                $this->ordersNb = 1;
                                break;
                            case 'S':
                                // On avance
                                $this->ordersNb = 2;
                                break;
                            case 'W':
                                // On tourne à gauche
                                $this->ordersNb = 0;
                                break;
                            case 'E':
                                // On tourne à droite
                                $this->ordersNb = 1;
                                break;
                        }
                    }
                } elseif ($this->myPosition->x != $this->objectif['X']) {
                    if($this->myPosition->x > $this->objectif['X']) {
                        var_dump('yolo3');
                        switch($this->myPosition->direction) {
                            case 'N':
                                // On tourne à gauche
                                var_dump('yoloa');
                                $this->ordersNb = 0;
                                break;
                            case 'S':
                                // On toune à droite
                                var_dump('yolob');
                                $this->ordersNb = 1;
                                break;
                            case 'W':
                                //on tourne avance
                                var_dump('yoloc');
                                $this->ordersNb = 2;
                                break;
                            case 'E':
                                // On tourne à droite
                                var_dump('yolod');
                                $this->ordersNb = 1;
                                break;
                        }
                    } else {
                        var_dump('yolo4');
                        switch ($this->myPosition->direction) {
                            case 'N':
                                // On tourne à droite
                                $this->ordersNb = 1;
                                break;
                            case 'S':
                                // On tourne à gauche
                                $this->ordersNb = 0;
                                break;
                            case 'W':
                                // On tourne à droite
                                $this->ordersNb = 1;
                                break;
                            case 'E':
                                // On tourne avance
                                $this->ordersNb = 2;
                                break;
                        }
                    }
                } else {
                    switch ($this->objectif['direction']) {
                        case 'N':
                            switch ($this->myPosition->direction) {
                                case 'S':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                                case 'W':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                                case 'E':
                                    // On tourne à gauche
                                    $this->ordersNb = 0;
                                    break;
                            }
                            break;
                        case 'S':
                            switch ($this->myPosition->direction) {
                                case 'N':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                                case 'W':
                                    // On tourne à gauche
                                    $this->ordersNb = 0;
                                    break;
                                case 'E':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                            }
                            break;
                        case 'W':
                            switch ($this->myPosition->direction) {
                                case 'N':
                                    // On tourne à gauche
                                    $this->ordersNb = 0;
                                    break;
                                case 'S':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                                case 'E':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                            }
                            break;
                        case 'E':
                            switch ($this->myPosition->direction) {
                                case 'N':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                                case 'S':
                                    // On tourne à gauche
                                    $this->ordersNb = 0;
                                    break;
                                case 'W':
                                    // On tourne à droite
                                    $this->ordersNb = 1;
                                    break;
                            }
                            break;
                    }
                }
            }


        } elseif ($this->positionEnemy != null) {
            // Si on connais la position de l'ennemie

            // Si il nous a tiré dessus
            if ($this->direction != null) {
                // Si les robots sont en face à face
                if ($this->direction == $this->myPosition->direction) {
                    /* ***
                     [!] On tire dans tous les cas comme on ne peut pas connaitre la vie de l'ennemie
                    *** */
                    if ($this->life <= $this->lifeEnemy) {
                        // L'ennemie est en face avec la meme ou moins de vie, alors on tire !
                        $this->ordersNb = 3;
                    } else {
                        // On se casse !
                        if ($this->myPosition->direction == 'N') {
                            if ($this->goToRight == true) {
                                $this->ordersNb = 1;
                            } else {
                                $this->ordersNb = 0;
                            }
                        } elseif ($this->myPosition->direction == 'S') {
                            if ($this->goToRight == true) {
                                $this->ordersNb = 0;
                            } else {
                                $this->ordersNb = 1;
                            }
                        } elseif ($this->myPosition->direction == 'W') {
                            if ($this->goToUp == true) {
                                $this->ordersNb = 1;
                            } else {
                                $this->ordersNb = 0;
                            }
                        } elseif ($this->myPosition->direction == 'E') {
                            if ($this->goToUp == true) {
                                $this->ordersNb = 0;
                            } else {
                                $this->ordersNb = 1;
                            }
                        }
                    }
                } else {
                    if (($this->direction == 'N' && $this->myPosition->direction == 'S')) {
                        //Faire mieux ici
                        $this->ordersNb = rand(0, 1);
                    } else {
                        $this->ordersNb = 2;
                    }
                }
            } else {
                // RANDOM
                $this->ordersNb = rand(0, 3);
            }


        } else {
            // RANDOM
            $this->ordersNb = rand(0, 3);
        }

        $this->updatePosition();
        /*var_dump($this->board);
        var_dump($this->ascii_board);*/
        var_dump($this->myPosition);
        var_dump($this->positionEnemy);
        var_dump($this->objectif);
        return $orders[$this->ordersNb];
    }

    // met à jour la variable myPosition en fonction de l'ordre donné
    public function updatePosition()
    {
        switch ($this->ordersNb) {
            case 0:
                // On tourne à gauche
                switch ($this->myPosition->direction) {
                    case 'N':
                        $this->myPosition->direction = 'W';
                        break;
                    case 'W':
                        $this->myPosition->direction = 'S';
                        break;
                    case 'S':
                        $this->myPosition->direction = 'E';
                        break;
                    case 'E':
                        $this->myPosition->direction = 'N';
                        break;
                }
                break;

            case 1:
                // On tourne à droite
                switch ($this->myPosition->direction) {
                    case 'N':
                        $this->myPosition->direction = 'E';
                        break;
                    case 'W':
                        $this->myPosition->direction = 'N';
                        break;
                    case 'S':
                        $this->myPosition->direction = 'W';
                        break;
                    case 'E':
                        $this->myPosition->direction = 'S';
                        break;
                }
                break;

            case 2:
                // On avance
                switch ($this->myPosition->direction) {
                    case 'N':
                        $this->myPosition->y--;
                        break;
                    case 'W':
                        $this->myPosition->x--;
                        break;
                    case 'S':
                        $this->myPosition->y++;
                        break;
                    case 'E':
                        $this->myPosition->x++;
                        break;
                }
                break;
        }
    }

    public function inverseDirection($direction) {
        switch($direction) {
            case 'N':
                return $inverseDirection = 'S';
                break;
            case 'W':
                return $inverseDirection = 'E';
                break;
            case 'S':
                return $inverseDirection = 'N';
                break;
            case 'E':
                return $inverseDirection = 'W';
                break;
        }
    }

}