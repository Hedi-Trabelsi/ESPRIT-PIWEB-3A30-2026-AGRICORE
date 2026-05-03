<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


##[ORM\Entity]
class Testtable
{

    #[ORM\Column(type: "integer")]
    private int $tess;

    public function getTess(): int
    {
        return $this->tess;
    }

    public function setTess(int $value): self
    {
        $this->tess = $value;
        return $this;
    }
}
