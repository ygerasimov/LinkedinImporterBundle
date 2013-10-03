<?php 

namespace CCC\LinkedinImporterBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReceivePrivate extends AbstractType {
	
	private $_liService = null;
	
	public function __construct($li) {
		$this->_liService = $li;
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'csrf_protection' => false,
		));
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->setMethod('GET');
		$builder->add('state', 'hidden', array(
			'constraints' => array(
				new EqualTo(array('value' => $this->_liService->getState()))
			),
		));
		$builder->add('code', 'hidden');
		$builder->add('submit', 'submit');
	}
	
	public function getName() {
		return '';
	}
	
}