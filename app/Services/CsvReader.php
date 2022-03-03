<?php

namespace App\Services;

use RuntimeException;
use SplFileObject;

class CsvReader
{
    private readonly SplFileObject $file;
    private array $header = [];

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            echo("Cannot find seed file at '{$path}'");
        }

        $this->file = new SplFileObject($path);
    }

    public function next(): ?CsvRow
    {
        if ($this->file->eof()) {
            return null;
        }

        $row = $this->file->fgetcsv();

        if ($row === false) {
            throw new RuntimeException("Can not read row at line {$this->file->getCurrentLine()}");
        }

        if (!isset($row[0])) {
            return null;
        }

        if (count($this->header) === 0) {
            $this->header = $row;

            return $this->next();
        }

        return new CsvRow($row, $this->header);
    }
}
