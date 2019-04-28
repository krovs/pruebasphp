<?php

class Flattener 
{
    var $xmlFile1;          // nombre fichero xml
    var $newFileName;       // nombre fichero final
    var $file;              // fichero final


    public function flatt($xmlFile1 = 'aplanamiento.xml', $newFileName = 'flattened.csv') {

        $this->xmlFile1 = $xmlFile1;
        $this->newFileName = $newFileName;

        if (file_exists($this->xmlFile1)) {
            // se carga el fichero xml con simplexml
            $xml = simplexml_load_file($this->xmlFile1);
            // se prepara el fichero final
            $this->file = fopen($this->newFileName, 'w');

            $headers = array(); // cabeceras del xml
            
            // por cada producto, se recorren sus cabeceras y se guardan
            foreach($xml->products->children() as $product) {
                foreach($product->children() as $item) {
                    if (!in_array($item->getName(), $headers)) {
                        $headers[] = $item->getName();
                    }
                }
            }
            // se escriben las cabeceras
            fputcsv($this->file, $headers, ';', '"');

            // por cada cabecera, se van tomando cada valor en orden
            foreach($xml->products->children() as $product) {
                $items = $product->children();
                // cada linea final de cada producto
                $line = array_fill(0, count($headers), "");

                foreach($headers as $index=>$header) {
                    foreach($items as $item) {
                        // si la cabecera esta contenida en el producto
                        if($item->getName() == $header) {
                            $line[$index] = $item;
                            break;
                        }
                    }
                }
                // se escribe cada linea de producto
                fputcsv($this->file, $line, ';', '"');
            }
            // se cierra el fichero
            fclose($this->file);

            print("Fichero $this->newFileName generado\n");
        }
        else {
            print("El fichero no existe\n");
        }
    }
}