<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Testtable
{

    #[ORM\Column(type: "integer")]
    private int $tess;

    public function getTess()
    {
        return $this->tess;
    }

    public function setTess($value)
    {
        $this->tess = $value;
    }
}
