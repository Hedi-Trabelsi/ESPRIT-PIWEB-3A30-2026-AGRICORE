<?php

namespace App\Tests\Form;

use App\Entity\Evennementagricole;
use App\Form\EvennementagricoleType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class EvennementagricoleTypeTest extends TypeTestCase
{
    /**
     * @return array<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }
    public function testSubmitValidData(): void
    {
        $dateDebut = new \DateTime('2026-06-01 10:00:00');
        $dateFin = new \DateTime('2026-06-02 18:00:00');

        $formData = [
            'titre' => 'Test Valid Event',
            'description' => 'This is a valid test description that is long enough.',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-01T10:00:00',
            'date_fin' => '2026-06-02T18:00:00',
            'frais_inscription' => 50,
            'capacite_max' => 100,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);

        $expected = new Evennementagricole();
        $expected->setTitre('Test Valid Event');
        $expected->setDescription('This is a valid test description that is long enough.');
        $expected->setLieu('Valid Location');
        $expected->setDateDebut($dateDebut);
        $expected->setDateFin($dateFin);
        $expected->setFraisInscription(50);
        $expected->setCapaciteMax(100);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected->getTitre(), $model->getTitre());
        $this->assertEquals($expected->getDescription(), $model->getDescription());
        $this->assertEquals($expected->getLieu(), $model->getLieu());
        $this->assertEquals($expected->getDateDebut(), $model->getDateDebut());
        $this->assertEquals($expected->getDateFin(), $model->getDateFin());
        $this->assertEquals($expected->getFraisInscription(), $model->getFraisInscription());
        $this->assertEquals($expected->getCapaciteMax(), $model->getCapaciteMax());
    }

    public function testSubmitInvalidShortTitle(): void
    {
        $formData = [
            'titre' => 'Hi',
            'description' => 'Valid description here that is long enough.',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-01T10:00:00',
            'date_fin' => '2026-06-02T18:00:00',
            'frais_inscription' => 50,
            'capacite_max' => 100,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidShortDescription(): void
    {
        $formData = [
            'titre' => 'Valid Title Here',
            'description' => 'Too short',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-01T10:00:00',
            'date_fin' => '2026-06-02T18:00:00',
            'frais_inscription' => 50,
            'capacite_max' => 100,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidDateFinBeforeDateDebut(): void
    {
        $formData = [
            'titre' => 'Valid Title Here',
            'description' => 'Valid description here that is long enough.',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-02T10:00:00',
            'date_fin' => '2026-06-01T18:00:00',
            'frais_inscription' => 50,
            'capacite_max' => 100,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidNegativeFrais(): void
    {
        $formData = [
            'titre' => 'Valid Title Here',
            'description' => 'Valid description here that is long enough.',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-01T10:00:00',
            'date_fin' => '2026-06-02T18:00:00',
            'frais_inscription' => -10,
            'capacite_max' => 100,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidZeroCapacite(): void
    {
        $formData = [
            'titre' => 'Valid Title Here',
            'description' => 'Valid description here that is long enough.',
            'lieu' => 'Valid Location',
            'date_debut' => '2026-06-01T10:00:00',
            'date_fin' => '2026-06-02T18:00:00',
            'frais_inscription' => 50,
            'capacite_max' => 0,
        ];

        $model = new Evennementagricole();
        $form = $this->factory->create(EvennementagricoleType::class, $model);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }
}
