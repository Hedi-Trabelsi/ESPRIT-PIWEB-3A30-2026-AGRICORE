<?php

namespace App\Tests\Form;

use App\Entity\Participants;
use App\Form\ParticipantsType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ParticipantsTypeTest extends TypeTestCase
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
        $formData = [
            'nom_participant' => 'Valid Participant Name',
            'email' => 'valid@example.com',
            'nbr_places' => 2,
        ];

        $model = new Participants();
        $form = $this->factory->create(ParticipantsType::class, $model, [
            'max_places' => 5,
        ]);

        $expected = new Participants();
        $expected->setNomParticipant('Valid Participant Name');
        $expected->setEmail('valid@example.com');
        $expected->setNbrPlaces(2);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testSubmitInvalidShortName(): void
    {
        $formData = [
            'nom_participant' => 'Hi',
            'email' => 'valid@example.com',
            'nbr_places' => 2,
        ];

        $model = new Participants();
        $form = $this->factory->create(ParticipantsType::class, $model, [
            'max_places' => 5,
        ]);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidEmail(): void
    {
        $formData = [
            'nom_participant' => 'Valid Participant Name',
            'email' => 'invalid-email',
            'nbr_places' => 2,
        ];

        $model = new Participants();
        $form = $this->factory->create(ParticipantsType::class, $model, [
            'max_places' => 5,
        ]);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidZeroPlaces(): void
    {
        $formData = [
            'nom_participant' => 'Valid Participant Name',
            'email' => 'valid@example.com',
            'nbr_places' => 0,
        ];

        $model = new Participants();
        $form = $this->factory->create(ParticipantsType::class, $model, [
            'max_places' => 5,
        ]);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }

    public function testSubmitInvalidTooManyPlaces(): void
    {
        $formData = [
            'nom_participant' => 'Valid Participant Name',
            'email' => 'valid@example.com',
            'nbr_places' => 10,
        ];

        $model = new Participants();
        $form = $this->factory->create(ParticipantsType::class, $model, [
            'max_places' => 5,
        ]);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
    }
}
