#!/bin/env php
<?php
// Mettre dans un tableau tout les nom des fichier en fonction du repertoire
if (!empty($argv[1]))
    run($argv[1]);
else
    echo "\033[31mErreur : Mauvaise commande, utilisez -h. \033[0m \n";

function run($way) {
    $list_file = array();

    if (!empty($way) && is_dir($way)) {
        if ($dh = opendir($way)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    $t = explode('.', $file);
                    $extension = end($t);
                    if ($extension == "c" || $extension == "h") {
                        array_push($list_file, $file);
                    }
                }
            }
            closedir($dh);
            start_scan($list_file, $way);
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

function space_end($line_content, $file_name, $nbr_line) {
    if (substr($line_content, -2, 2) == " \n") {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbr_line : Espace en fin de ligne. \n";
        return(true);
    }
    return(false);
}

function define_in_c($line_content, $file_name, $nbr_line) {
    if (substr($file_name, -1, 1) == "c" && preg_match('(#define)', $line_content)) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbr_line : Define dans un C. \n";
        return(true);
    }
    return(false);
}

function too_much_charac($line_content, $file_name, $nbr_line) {
    if (strlen($line_content) > 80) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbr_line : Ligne de plus de 80 caractères. \n";
        return(true);
    }
    return(false);
}

function space_miss_after_key_word($line_content, $file_name, $nbr_line) {
    if (preg_match('(\b(if|while|for|return)\()', $line_content)) {
        echo "\033[31m Erreur\033[0m : $file_name : ligne $nbr_line : Espace manquant après mot clé. \n";
        return(true);
    }
    return(false);
}

function space_miss_after_comma($line_content, $file_name, $nbr_line) {
    $error = 0;
    preg_match_all('/,./', $line_content, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        for ($a = 0; $a < count($matches); $a++) {
            if ($matches[$a][0] != ", ") {
                echo "\033[31m Erreur\033[0m : $file_name : ligne $nbr_line : Espace manquant après virgule. \n";
                $error++;
            }

        }
    }
    return($error);
}

// Verification
function start_scan($list_file, $way)
{
    $nbr_error = 0;
    $nbr_dossier = 0;

    for ($i = 0; $i < count($list_file); $i++) {
        $in_func = FALSE;
        $line_in_func = 0;
        $nbr_func = 0;
        $tmp_line = 0;
        $check_next = FALSE;
        echo "\nScan: $list_file[$i] \n";
        $selected_file = $way . $list_file[$i];

        if (is_readable($selected_file)) {
            $line = file($selected_file);
            foreach ($line as $nbr_line => $line_content) {
                $nbr_line = $nbr_line + 1;
                $file_name = $list_file[$i];

                // ===== Define dans un C
                if (define_in_c($line_content, $file_name, $nbr_line))
                    $nbr_error++;
                // =========

                // ===== Espace en fin de ligne
                if (space_end($line_content, $file_name, $nbr_line))
                    $nbr_error++;
                // ==========

                // ===== Double retour à la ligne
                if ($line_content == "\n" && $tmp_line == 0) {
                    $tmp_line = $nbr_line;
                }
                if ($line_content == "\n" && $tmp_line == $nbr_line - 1) {
                    echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Double retour à la ligne. \n";
                    $nbr_error++;
                    $tmp_line = 0;
                }
                // ==========

                // ===== Ligne de plus de 80 caractères
                if (too_much_charac($line_content, $file_name, $nbr_line))
                    $nbr_error++;
                // ==========

                // ===== Espace manquant après mot clé
                if (space_miss_after_key_word($line_content, $file_name, $nbr_line))
                    $nbr_error++;
                // ==========

                // ===== Espace manquant après virgule
                $nbr_error += space_miss_after_comma($line_content, $file_name, $nbr_line);
                // ==========

                // ===== Declaration / affectation meme ligne
                $d = preg_match('(char|int|float|double)', $line_content);
                $a = preg_match('(=)', $line_content);
                if ($d == 1 && $a == 1) {
                  echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Declaration et Affectation sur la même ligne\n";
                  $nbr_error++; 
              }
                // ==========

                // ===== Tabulation sur declaration
              $decla = preg_match('(char|int|float|double|void)', $line_content);
              $tab = preg_match('(\t)', $line_content);
              if ($decla == 1 && $tab != 1) {
                  echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Pas de tabulations dans les déclarations\n";
                  $nbr_error++;
              }
                // ==========

              // ===== Saut de ligne apres declaration
              $dec = preg_match('(^.+[a-zA-Z].=)', $line_content);
              if ($dec == 1) {
                  $check_next = TRUE;
              }
              if ($check_next == TRUE && $dec != 1 && $line_content != "\n") {
                  echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Pas de saut de ligne après les déclarations\n";
                  $check_next = FALSE;
                  $nbr_error++;
              }
              // ==========

                // ===== Mauvais header / Triche Edouard MOULINETTE
              if ($nbr_dossier != sizeof($list_file)) {
                if ($nbr_line >= 2 && $nbr_line <= 8) {
                    if (preg_match('(\*\*)', $line_content) == 1) {
                        if (preg_match('(moulin_e@etna-alternance.net)', $line_content)) {
                            echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : TRICHE (moulin_e@etna-alternance.net)\n";
                            $nbr_error++;
                        }
                        if (preg_match('(MOULINETTE Edouard)', $line_content)) {
                            echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : TRICHE (MOULINETTE Edouard) \n";
                            $nbr_error++;
                        }
                    } else {
                        echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Header oublié \n";
                        $nbr_error++;
                    }
                }
                if ($nbr_line == 1 || $nbr_line == 9) {
                    $l1 = preg_match('(\/\*)', $line_content);
                    $l9 = preg_match('(\*\/)', $line_content);
                    if ($nbr_line == 1 && $l1 != 1) {
                      echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Header oublié \n";
                      $nbr_error++;
                  }
                  if ($nbr_line == 9 && $l9 != 1) {
                      echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Header oublié \n";
                      $nbr_error++;
                  }
              }
          }
                // =========

                // ===== Fonctions de plus de 25 lignes & Nombre de fonctions par fichier
          if (preg_match('/^{/', $line_content) && $in_func == FALSE) {
            $in_func = TRUE;
        } elseif (preg_match('/^}/', $line_content)) {
            $in_func = FALSE;
            $nbr_func = $nbr_func + 1;
            $line_in_func = 0;
        } else {
            if ($in_func == TRUE) {
                $line_in_func = $line_in_func + 1;
            }
            if ($line_in_func > 25) {
                echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Fonctions de plus de 25 lignes. \n";
                $nbr_error++;
            }
        }
                // ==========

                // ===== Plus de 4 paramètres pour une fonction
        $nbr_param = explode(',', $line_content);
        if (count($nbr_param) > 4) {
            echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Plus de 4 paramètres pour une fonction. \n";
            $nbr_error++;
        }
                // ==========
    }
            // ===== Show NbFunc
    if ($nbr_func > 5) {
        echo "\033[31m Erreur\033[0m : $list_file[$i] : ligne $nbr_line : Il y a $nbr_func function dans le fichier. \n";
        $nbr_error++;
        $nbr_func = 0;
    }
            // ==========
}
else
    echo "\033[31mErreur : Fichier non lisible. \033[0m \n";
}
    // ===== Show NbError
if ($nbr_error == 0)
    echo "\033[32mAucune fautes de normes\033[0m. \n";
else
    echo "Vous avez fait \033[31m$nbr_error\033[0m fautes de norme. \n";
    // ==========
}
?>