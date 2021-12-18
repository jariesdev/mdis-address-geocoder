<?php

namespace App\MsAccessReader;

use Illuminate\Support\Facades\DB;
use PDO;

class MsAccessReader
{
    protected $mbdPath;
    protected $driver = 'MDBTools';
    protected $pdoInstance;
    /**
     * @var string
     */
    private $path;

    public function __construct(string $mbdPath, string $driver = 'MDBTools')
    {
        $this->$mbdPath = $mbdPath;
        $this->driver = $driver;
        $this->pdoInstance = $this->createConnection($this->mbdPath, $this->driver);
    }

    /**
     * @param  string  $mbdPath
     * @param  string  $driver
     * @return PDO
     */
    public function createConnection(string $mbdPath, string $driver): PDO
    {
        return new  PDO("odbc:Driver=$driver;DBQ=$mbdPath;");
    }
}
