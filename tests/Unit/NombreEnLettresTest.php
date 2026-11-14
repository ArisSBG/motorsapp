<?php

namespace Tests\Unit;

use App\Support\Montant;
use App\Support\NombreEnLettres;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NombreEnLettresTest extends TestCase
{
    public static function exemples(): array
    {
        return [
            [0, 'zéro'],
            [21, 'vingt et un'],
            [71, 'soixante et onze'],
            [80, 'quatre-vingts'],
            [81, 'quatre-vingt-un'],
            [91, 'quatre-vingt-onze'],
            [99, 'quatre-vingt-dix-neuf'],
            [200, 'deux cents'],
            [201, 'deux cent un'],
            [1_000, 'mille'],
            [6_500, 'six mille cinq cents'],
            [41_000, 'quarante et un mille'],
            [80_000, 'quatre-vingt mille'],
            [200_000, 'deux cent mille'],
            [500_000, 'cinq cent mille'],
            [975_000, 'neuf cent soixante-quinze mille'],
            [1_000_000, 'un million'],
            [2_480_000, 'deux millions quatre cent quatre-vingt mille'],
        ];
    }

    #[DataProvider('exemples')]
    public function test_conversion(int $nombre, string $attendu): void
    {
        $this->assertSame($attendu, NombreEnLettres::convertir($nombre));
    }

    public function test_format_des_montants(): void
    {
        $this->assertSame('975.000 FCFA', Montant::format(975_000));
        $this->assertSame('0 FCFA', Montant::format(null));
        $this->assertSame('6.500', Montant::format(6_500, devise: false));
    }
}
