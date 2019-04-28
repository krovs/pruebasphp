<?php

class Merger 
{
    var $csvFile1;          // nombre fichero csv 1
    var $csvFile2;          // nombre fichero csv 2
    var $newFileName;       // nombre fichero final
    var $file;              // fichero final
    var $finalHeaders;      // cabeceras finales


    public function merge($csvFile1 = 'csvfile1.csv', $csvFile2 = 'csvfile2.csv', $newFileName = 'merged.csv') {

        $this->csvFile1 = $csvFile1;
        $this->csvFile2 = $csvFile2;
        $this->newFileName = $newFileName;

        if (file_exists($this->csvFile1) && file_exists($this->csvFile2)) {
            // se toman las cabeceras de cada fichero y se borran dejando las lineas
            $csv1 = array_map('str_getcsv', file($this->csvFile1));
            $csv1headers = $csv1[0];
            array_shift($csv1);
            $csv2 = array_map('str_getcsv', file($this->csvFile2));
            $csv2headers = $csv2[0];
            array_shift($csv2);

            // se unen las cabeceras de cada fichero
            $this->finalHeaders = array_unique(array_merge($csv1headers, $csv2headers));
            
            // se prepara el fichero final
            $this->file = fopen($this->newFileName, 'w');

            // se escribe la cabecera
            fputcsv($this->file, $this->finalHeaders, ',', '"');

            // se escriben las lineas de cada fichero
            $this->setLines($csv1, $csv1headers);
            $this->setLines($csv2, $csv2headers);

            // se cierra el fichero
            fclose($this->file);

            print("Fichero $this->newFileName generado\n");
        }
        else {
            print("Uno o mas ficheros no existen\n");
        }
    }

    // calcula las posiciones y escribe las lineas
    function setLines($csv, $csvheaders) {
        foreach($csv as $csvline) {
            // se crea y rellena una linea del tamano del header final
            $line = array_fill(0, count($this->finalHeaders), "");
            // por cada elemento de la linea, se mete el elemento en la posicion de la cabecera final
            foreach($csvline as $index=>$element) {
                $pos = array_search($csvheaders[$index], $this->finalHeaders);
                $line[$pos] = $element;
            }
            fputcsv($this->file, $line, ',', '"');
        }
    }
}