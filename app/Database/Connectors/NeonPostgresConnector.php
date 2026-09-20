<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    protected function addSslOptions($dsn, array $config)
    {
        $dsn = parent::addSslOptions($dsn, $config);

        if (! empty($config['neon_endpoint'])) {
            $dsn .= ';options=endpoint='.$config['neon_endpoint'];
        }

        return $dsn;
    }
}
