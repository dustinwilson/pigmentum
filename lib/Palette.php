<?php
declare(strict_types=1);
namespace dW\Pigmentum;

class Palette {
    public ?string $name;
    protected array $colors = [];


    public function __construct(?string $name = null) {
        $this->name = $name;
    }


    public function addColor(Color ...$colors): bool {
        foreach ($colors as $c) {
            $this->colors[] = $c;
        }

        return true;
    }

    // Outputs Adobe Photoshop's ACO format
    public function saveACO(): string {
        if (count($this->colors) === 0) {
            throw new \Exception("There must be a color in the palette to be able to save as a palette file format.\n");
        }

        $output = '';

        // Do the version 1 swatches first for backwards compatibility reasons.
        $count = count($this->colors, COUNT_RECURSIVE);
        $output .= pack('nn', 1, $count);

        $swatches = [];
        foreach ($this->colors as $i => $c) {
            // Yes, 256 seems to be right here ¯\_(ツ)_/¯
            $ch = [
                round($c->Lab->L * 100) / 256,
                round($c->Lab->a * 100) / 256,
                round($c->Lab->b * 100) / 256
            ];

            $chm = [
                round($c->Lab->L * 100) % 255,
                round($c->Lab->a * 100) % 255,
                round($c->Lab->b * 100) % 255
            ];

            $pack = 'n';
            foreach ($chm as $k => $v) {
                $chm[$k] = $v - $ch[$k];

                $pack .= ($ch[$k] >= 0) ? 'C' : 'c';

                if ($chm[$k] >= 0) {
                    $pack .= 'C';
                    $chm[$k] = ceil($chm[$k]);
                } else {
                    $pack .= 'c';
                    $chm[$k] = floor($chm[$k]);
                }

                $ch[$k] = floor($ch[$k]);
            }
            $pack .= 'n';

            $swatches[$i] = pack($pack, 7, $ch[0], $chm[0], $ch[1], $chm[1], $ch[2], $chm[2], 0);
            $output .= $swatches[$i];
        }

        // Then, the version 2 swatch data...
        $output .= pack('nn', 2, $count);
        foreach ($swatches as $i => $s) {
            // Swatches in Version 2 are identical. The difference is the name which comes
            // after.
            $output .= $s;

            $output .= pack('n', 0);
            $name = mb_convert_encoding($this->colors[$i]->name, 'UTF-16');
            $output .= pack('n', mb_strlen($name, 'UTF-16') + 1);
            $output .= $name;

            $output .= pack('n', 0);
        }

        return $output;
    }

    // Outputs to Adobe Photoshop's ACO format
    public function saveACOFile(string $filename): bool {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            throw new \Exception("Directory \"$dirname\" does not exist.\n");
        }
        if (!is_writable($dirname)) {
            throw new \Exception("Directory \"$dirname\" is not writable.\n");
        }

        file_put_contents($filename, $this->saveACO());
        return true;
    }

    // Outputs Adobe Photoshop's ACT 256 color palette format
    public function saveACT(): string {
        $colorsCount = count($this->colors);
        if ($colorsCount === 0) {
            throw new \Exception("There must be a color in the palette to be able to save as a palette file format.\n");
        } elseif ($colorsCount > 256) {
            $trace = debug_backtrace();
            trigger_error("Photoshop's ACT format can only have up to 256 colors; truncating in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_WARNING);
            $colors = array_slice($this->colors, 0, 256);
        } else {
            $colors = $this->colors;
        }

        $output = '';

        foreach ($colors as $c) {
            $output .= pack('C*', $c->RGB->R, $c->RGB->G, $c->RGB->B);
        }

        if ($colorsCount < 256) {
            $stop = 256 - $colorsCount;
            for ($i = 0; $i < $stop; $i++) {
                $output .= pack('x3');
            }

            $output .= pack('xC3', $colorsCount, 255, 255);
        }

        return $output;
    }

    // Outputs to Adobe Photoshop's ACT format
    public function saveACTFile(string $filename): bool {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            throw new \Exception("Directory \"$dirname\" does not exist.\n");
        }
        if (!is_writable($dirname)) {
            throw new \Exception("Directory \"$dirname\" is not writable.\n");
        }

        file_put_contents($filename, $this->saveACT());
        return true;
    }

    // Outputs to ArtRage's COL format
    public function saveCOL(): string {
        if (count($this->colors) === 0) {
            throw new \Exception("There must be a color in the palette to be able to save as a palette file format.\n");
        }

        $count = count($this->colors, COUNT_RECURSIVE);
        $output = implode("\0", str_split('AR2 COLOR PRESET', 1)) . "\0";
        $output .= pack('CxCxCCxC', 13, 10, 143, 48, 255);
        $output .= pack('PV', 48 + ($count * 10), $count);

        // ArtRage only supports RGB for its palettes :/
        $names = '';
        foreach ($this->colors as $c) {
            $output .= pack('C*', $c->RGB->B, $c->RGB->G, $c->RGB->R, 255);
            $names .= (isset($c->name)) ? implode("\0", str_split($c->name, 1)) . "\0" : pack('cxcx', 37, 37);
            $names .= pack('xx');
        }

        $output .= implode("\0", str_split('ARSwatchFileVersion-3', 1)) . "\0";
        $output .= pack('xx') . $names;

        return $output;
    }

    public function saveCOLFile(string $filename): bool {
        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            throw new \Exception("Directory \"$dirname\" does not exist.\n");
        }
        if (!is_writable($dirname)) {
            throw new \Exception("Directory \"$dirname\" is not writable.\n");
        }

        file_put_contents($filename, $this->saveCOL());
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
        $colorSet->setAttribute('name', $this->name);
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
        if ($zip->open($tmpfile, \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create temporary file \"$filename\".\n");
        }

        $zip->addFromString('colorset.xml', $dom->saveXML($colorSet));
        $zip->addFromString('mimetype', 'krita/x-colorset');
        $zip->addFromString('profiles.xml', '<Profiles/>');

        $zip->close();
        return $tmpfile;
    }
}
