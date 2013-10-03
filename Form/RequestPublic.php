<?php 

namespace CCC\LinkedinImporterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Url;

class RequestPublic extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('url', 'text', array('constraints' => new Url()));
		$builder->add('public', 'submit');
	}
	
	public function getName() {
		return 'requestpublic';
	}
	
}