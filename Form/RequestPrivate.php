<?php

namespace CCC\LinkedinImporterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestPrivate extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('private', 'submit');
    }

    public function getName() {
        return 'privaterequest';
    }

}
