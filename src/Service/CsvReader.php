<?php

namespace App\Service;

class CsvReader
{
    public function getData($csv): array
    {
        $rows = [];

        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                $i++;
                if ($i == 1) continue;

                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }
}