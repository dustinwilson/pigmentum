<?php
declare(strict_types=1);
namespace dW\Pigmentum;

class Palette {
    protected $colors = [];
    protected $_name = '';

    public function __construct(string $name = '') {
        $this->_name = $name;
    }

    public function addColor(Color ...$colors): bool {
        foreach ($colors as $c) {
            $this->colors[] = $c;
        }

        return true;
    }

    public function saveKPL(int $columnCount = 8, bool $readonly = false): string {
        $tmpfile = $this->createKPLTemp($columnCount, $readonly);
        $output = file_get_contents($tmpfile);
        unlink($tmpfile);
        return $output;
    }

    // Outputs to Krita's KPL format
    public function saveKPLFile(string $filename, int $columnCount = 8, bool $readonly = false): bool {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            throw new \Exception("Directory \"$dirname\" does not exist.\n");
        }
        if (!is_writable($dirname)) {
            throw new \Exception("Directory \"$dirname\" is not writable.\n");
        }

        rename($this->createKPLTemp($columnCount, $readonly), $filename);
        return true;
    }

    protected function createKPLTemp(int $columnCount = 8, bool $readonly = false): string {
        if (count($this->colors) === 0) {
            throw new \Exception("There must be a color in the palette to be able to save as a palette file format.\n");
        }

        $dom = new \DOMDocument();
        $colorSet = $dom->createElement('ColorSet');
        $colorSet->setAttribute('readonly', ($readonly) ? 'true' : 'false');
        $colorSet->setAttribute('version', '1.0');
        $colorSet->setAttribute('name', $this->_name);
        $colorSet->setAttribute('columns', (string)$columnCount);

        $row = 0;
        $column = 0;
        foreach ($this->colors as $c) {
            $entry = $dom->createElement('ColorSetEntry');
            $entry->setAttribute('bitdepth', 'U8');
            $entry->setAttribute('name', $c->name);
            $entry->setAttribute('spot', 'true');

            $lab = $dom->createElement('Lab');
            $lab->setAttribute('L', (string)($c->Lab->L / 100));
            $lab->setAttribute('a', (string)(($c->Lab->a + 128) / 255));
            $lab->setAttribute('b', (string)(($c->Lab->b + 128) / 255));
            $lab->setAttribute('space', 'Lab identity built-in');
            $entry->appendChild($lab);

            $pos = $dom->createElement('Position');
            $pos->setAttribute('column', (string)$column);
            $pos->setAttribute('row', (string)$row);
            $entry->appendChild($pos);

            $colorSet->appendChild($entry);

            $column++;
            if ($column === $columnCount) {
                $column = 0;
                $row++;
            }
        }

        $dom->appendChild($colorSet);
        $dom->formatOutput = true;
        $colorSet->setAttribute('rows', (string)$row);

        $tmpfile = tempnam(sys_get_temp_dir(), 'pigmentum');

        $zip = new \ZipArchive();
        if ($zip->open($tmpfile, \ZipArchive::CREATE) !== true) {
            throw new \Exception("Cannot create temporary file \"$filename\".\n");
        }

        $zip->addFromString('colorset.xml', $dom->saveXML($colorSet));
        $zip->addFromString('mimetype', 'krita/x-colorset');
        $zip->addFromString('profiles.xml', '<Profiles/>');

        $zip->close();
        return $tmpfile;
    }

    public function __get(string $prop) {
        if ($prop === 'name') {
            return $this->_name;
        }
    }

    public function __set(string $prop, $value) {
        if ($prop === 'name') {
            $this->_name = $value;
        }
    }
}
