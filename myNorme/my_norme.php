#!/bin/env php
<?php
// Mettre dans un tableau tout les nom des fichier en fonction du repertoire
run($argv[1]);

function run($way) {
    $listFile = array();
    if (!empty($way) && is_dir($way)) {
        if ($dh = opendir($way)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    $t = explode('.', $file);
                    $extension = end($t);
                    if ($extension == "c" || $extension == "h") {
                        array_push($listFile, $file);
                    }
                }
            }
            closedir($dh);
            start_scan($listFile, $way);
        }
    }
    elseif ($way == "-h" || $way == "--help") {
        echo " php my_norme.php [OPTION] | [CHEMIN] \n";
        echo " -h --help Afficher un message d'aide montrant toutes les fonctions. \n";
        echo " -c --no-colors Désactiver les couleurs. \n";
        echo " -M --makefile Activer la vérification du Makefile. \n";
        echo " -m --malloc Activer la vérification des malloc. \n";
        echo " -C --comment Activer la vérification des commentaires. \n";
        echo " -a --authorised Activer la vérification des fonctions autorisées. \n";
    }
    else {
        echo "\033[31mErreur : Mauvaise commande, utilisez -h. \033[0m \n";
    }
}

function space_end($contenuLigne, $file_name, $nbLigne) {
    if (substr($contenuLigne, -2, 2) == " \n") {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbLigne : Espace en fin de ligne. \n";
        return(true);
    }
    return(false);
}

function define_in_c($contenuLigne, $file_name, $nbLigne) {
    if (substr($file_name, -1, 1) == "c" && preg_match('(#define)', $contenuLigne)) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbLigne : Define dans un C. \n";
        return(true);
    }
    return(false);
}

function too_much_charac($contenuLigne, $file_name, $nbLigne) {
    if (strlen($contenuLigne) > 80) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbLigne : Ligne de plus de 80 caractères. \n";
        return(true);
    }
    return(false);
}

function space_miss_after_key_word($contenuLigne, $file_name, $nbLigne) {
    if (preg_match('(\b(if|while|for|return)\()', $contenuLigne)) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbLigne : Espace manquant après mot clé. \n";
        return(true);
    }
    return(false);
}

function space_miss_after_comma($contenuLigne, $file_name, $nbLigne) {
    $error = 0;
    preg_match_all('/,./', $contenuLigne, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        for ($a = 0; $a < count($matches); $a++) {
            if ($matches[$a][0] != ", ") {
                echo "\033[31m Erreur\033[0m : $file_name : ligne $nbLigne : Espace manquant après virgule. \n";
                $error++;
            }
        }
    }
    return($error);
}

// Verification
function start_scan($listFile, $way) {

    $nbError = 0;
    $nbDossier = 0;

    for ($i = 0; $i < count($listFile); $i++) {
        $inFunc = FALSE;
        $ligneInFunc = 0;
        $nbFunc = 0;
        $tmpL = 0;
        $fichier = $way . $listFile[$i];
        $ligne = file($fichier);
        echo "\nScan: $listFile[$i] \n";
        foreach ($ligne as $nbLigne => $contenuLigne) {
            $nbLigne = $nbLigne + 1;
            $file_name = $listFile[$i];

            /*
            if (preg_match('[d]', $contenuLigne)) {
                echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Contien : d \n";
                $nbError++;
            }*/

            // ===== Define dans un C
            if (define_in_c($contenuLigne, $file_name, $nbLigne))
                $nbError++;
            // =========

            // ===== Espace en fin de ligne
            if (space_end($contenuLigne, $file_name, $nbLigne))
                $nbError++;
            // ==========

            // ===== Double retour à la ligne
            if ($contenuLigne == "\n" && $tmpL == 0) {
                $tmpL = $nbLigne;
            }
            if ($contenuLigne == "\n" && $tmpL == $nbLigne - 1) {
                echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Double retour à la ligne. \n";
                $nbError++;
                $tmpL = 0;
            }
            // ==========

            // ===== Ligne de plus de 80 caractères
            if (too_much_charac($contenuLigne, $file_name, $nbLigne))
                $nbError++;
            // ==========

            // ===== Espace manquant après mot clé
            if (space_miss_after_key_word($contenuLigne, $file_name, $nbLigne))
                $nbError++;
            // ==========

            // ===== Espace manquant après virgule
            $nbError += space_miss_after_comma($contenuLigne, $file_name, $nbLigne);
            // ==========

            // ===== Mauvais header / Triche Edouard MOULINETTE
            if ($nbDossier != sizeof($listFile)) {
                if ($nbLigne >= 3 && $nbLigne <= 10) {
                    if (preg_match('(\/\/)', $contenuLigne) == 1) {
                        if (preg_match('(moulin_e@etna-alternance.net)', $contenuLigne)) {
                            echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : TRICHE (moulin_e@etna-alternance.net). \n";
                            $nbError++;
                        }
                        if (preg_match('(MOULINETTE Edouard)', $contenuLigne)) {
                            echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : TRICHE (MOULINETTE Edouard). \n";
                            $nbError++;
                        }
                    }
                    else {
                        echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Header oublié. \n";
                        $nbError++;
                    }
                }
            }
            // =========

            // ===== Fonctions de plus de 25 lignes & Nombre de fonctions par fichier
            if (preg_match('/^{/', $contenuLigne) && $inFunc == FALSE) {
                $inFunc = TRUE;
            }
            elseif (preg_match('/^}/', $contenuLigne)) {
                $inFunc = FALSE;
                $nbFunc = $nbFunc + 1;
                $ligneInFunc = 0;
            }
            else {
                if ($inFunc == TRUE) {
                    $ligneInFunc = $ligneInFunc + 1;
                }
                if ($ligneInFunc > 25) {
                    echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Fonctions de plus de 25 lignes. \n";
                    $nbError++;
                }
            }
            // ==========

            // ===== Plus de 4 paramètres pour une fonction
            $nbParam = explode(',', $contenuLigne);
            if (count($nbParam) > 4) {
                echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Plus de 4 paramètres pour une fonction. \n";
                $nbError++;
            }
            // ==========
        }
        // ===== Show NbFunc
        if ($nbFunc > 5) {
            echo "\033[31m Erreur\033[0m : $listFile[$i] : ligne $nbLigne : Il y a $nbFunc function dans le fichier. \n";
            $nbError++;
            $nbFunc = 0;
        }
        // ==========
    }
    if ($nbError == 0) {
        echo "\033[32mAucune fautes de normes\033[0m. \n";
    }
    else {
        echo "Vous avez fait \033[31m$nbError\033[0m fautes de norme. \n";
    }
}
?>