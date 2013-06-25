<?php
/* Positionable Test cases generated on: 2011-10-17 16:12:13 : 1318860733*/
App::import('Behavior', 'Positionable.Positionable');

class MockPositionableBehavior extends PositionableBehavior {
	public function _getModels() {
		return array('PositionableElement', 'PositionableItem', 'ListElement', 'PositionByItselfElement');
	}
}

class PositionableElement extends CakeTestModel {
	public $useTable = 'positionable_elements';
	public $actsAs = array(
		'MockPositionable' => array('foreignKey' => 'foreign_model_id', 'model' => 'PositionableAssociated'),
	);
	public $alias = 'PositionableElement';
	public $recursive = -1;
	public $belongsTo = array('PositionableAssociated' => array('foreignKey' => 'foreign_model_id'));
}

class PositionableItem extends CakeTestModel {
	public $useTable = 'positionable_items';
	public $actsAs = array(
		'MockPositionable' => array('foreignKey' => 'foreign_model_id', 'model' => 'PositionableAssociated')
	);
	public $alias = 'PositionableItem';
	public $recursive = -1;
}

class ListElement extends CakeTestModel {
	public $useTable = 'list_elements';
	public $actsAs = array(
		'MockPositionable' => array('foreignKey' => 'list_id', 'model' => 'List')
	);
	public $alias = 'ListElement';
}

class PositionByItselfElement extends CakeTestModel {
	public $useTable = 'list_elements';
	public $actsAs = array(
		'MockPositionable' => array('foreignKey' => 'list_id', 'model' => 'PositionByItselfElement'),
	);
	public $alias = 'PositionByItselfElement';

	public function beforeValidate($options = array()) {
		$this->data[$this->alias]['foreign_model_id'] = 'foreign-model-1';
		return parent::beforeValidate($options);
	}
}

class PositionableErrorMissingFKField extends CakeTestModel {
	public $useTable = 'not_positionable_elements';
	public $actsAs = array(
		'MockPositionable' => array('foreignKey' => 'foreign_model_id', 'model' => 'PositionableAssociated')
	);
	public $alias = 'NotPositionableElement';
}

class PositionableErrorNoFKDefine extends CakeTestModel {
	public $useTable = 'positionable_items';
	public $actsAs = array('MockPositionable');
	public $alias = 'NotPositionableElement';
}

class PositionableAssociated extends CakeTestModel {
	public $useTable = 'positionable_associated';
	public $alias = 'PositionableAssociated';
	public $hasOne = array('PositionableElement' => array('foreignKey' => 'foreign_model_id'));
}

class PositionableBehaviorTest extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.positionable.list_element',
		'plugin.positionable.positionable_element',
		'plugin.positionable.positionable_item',
		'plugin.positionable.not_positionable_element',
		'plugin.positionable.positionable_associated',
	);

/**
 * Creates the model instance
 *
 * @param string $method
 * @return void
 */
	public function startTest($method) {
		parent::startTest($method);
		$this->PositionableElement = ClassRegistry::init('PositionableElement');
		$this->PositionableItem = ClassRegistry::init('PositionableItem');

		$fixture = new PositionableElementFixture();
		$this->_record = array('PositionableElement' => $fixture->records[0]);
	}

/**
 * Destroy the model instance
 *
 * @param string $method
 * @return void
 */
	public function endTest($method) {
		parent::endTest($method);
		unset($this->PositionableItem);
		unset($this->PositionableElement);
		ClassRegistry::flush();
	}

/**
 * Test if the behavior trigger an error if mandatory fields are forgotten
 *
 * @return void
 */
	public function testErrorsWhenMissingField() {
		$this->expectError('PHPUnit_Framework_Error_Notice');
		new PositionableErrorMissingFKField();
	}

/**
 * Test if the behavior trigger an error if mandatory fields are forgotten
 *
 * @return void
 */
	public function testErrorWhenFKIsNotDefined() {
		$this->expectError('PHPUnit_Framework_Error_Notice');
		new PositionableErrorNoFKDefine();
	}

/**
 * Test validation rules
 *
 * @return void
 */
	public function testRecordIsValide() {
		$this->assertValid($this->PositionableElement, $this->_record);
	}

	public function testMandatoryFields() {
		$data = array('PositionableElement' => array('id' => 'new-id'));
		$expectedErrors = array();
		$this->assertValidationErrors($this->PositionableElement, $data, $expectedErrors);
	}

	public function testPositionIsNotUnique_RecordsInSameTable() {
		$data = $this->_record;
		$data['PositionableElement']['id'] = 'new-id';
		$expectedErrors = array('position');
		$this->assertValidationErrors($this->PositionableElement, $data, $expectedErrors);
	}

	public function testPositionIsNotUnique_RecordsInDifferentTable() {
		$data = $this->_record;
		$data['PositionableElement']['id'] = 'new-id';
		$data['PositionableElement']['position'] = 3;
		$expectedErrors = array('position');
		$this->assertValidationErrors($this->PositionableElement, $data, $expectedErrors);
	}

	public function testIsPositionPositive () {
		$data = $this->_record;
		$data['PositionableElement']['position'] = '1000';
		$this->assertValid($this->PositionableElement, $data, array('positive'));
	}

	public function testPositionWithNaN () {
		$data = $this->_record;
		$data['PositionableElement']['position'] = 'NaN';
		$this->assertValidationErrors($this->PositionableElement, $data, array('position'));
	}

	public function testPositionWithNegativeNumber () {
		$data = $this->_record;
		$data['PositionableElement']['position'] = -1;
		$this->assertValidationErrors($this->PositionableElement, $data, array('position'));
	}

	public function testPositionWithZero () {
		$data = $this->_record;
		$data['PositionableElement']['position'] = 0;
		$this->assertValid($this->PositionableElement, $data);
	}

	public function testSamePositionCanBeUsePerDifferentPositionableAssociated() {
		$ListElement = new ListElement();
		$data = $ListElement->findById('list-element-1');
		$this->assertValid($ListElement, $data);
	}

/**
 * Test the move method
 *
 * @return void
 */
	public function testMoveException() {
		$this->expectException('NotFoundException');
		$this->PositionableElement->move('non-existing-positionable-element', 0);
	}

	public function testMoveDown() {
		$this->PositionableElement->move('positionable-element-2', 1);

		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-2')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 1);

		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-1')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 2);
	}

	public function testMoveUp() {
		$this->PositionableElement->move('positionable-element-1', 2);

		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-1')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 2);
		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-2')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 1);
	}

	public function testMoveAfterOtherTable() {
		$this->PositionableItem = ClassRegistry::init('PositionableItem');
		$this->PositionableElement->move('positionable-element-1', 3);

		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-1')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 3);

		$PositionableElement = $this->PositionableElement->find('first', array(
			'conditions' => array('PositionableElement.id' => 'positionable-element-2')
		));
		$position = $PositionableElement['PositionableElement']['position'];
		$this->assertEqual($position, 1);

		$PositionableItem = $this->PositionableItem->findById('positionable-item-1');
		$position = $PositionableItem['PositionableItem']['position'];
		$this->assertEqual($position, 2);
	}

	public function testMoveWhenItselfPositioned() {
		$PositionByItselfElement = ClassRegistry::init('PositionByItselfElement');

		$PositionByItselfElement->move('list-element-1', 2);
		$element = $PositionByItselfElement->findById('list-element-1');

		$this->assertEqual($element['PositionByItselfElement']['position'], 2);
	}

	public function testMoveUnpositionedElementPositionItAtTheEndWithoutUpdatingOthers() {
		$Model = ClassRegistry::init('PositionableElement');
		$preconditionsOk = $Model->updateAll(
			array('PositionableElement.position' => null),
			array('PositionableElement.id' => 'positionable-element-2')
		);
		$this->assertTrue($preconditionsOk);

		$success = $Model->move('positionable-element-2', 2);
		$newOrder = Hash::combine(
			$Model->find('all', array('order' => 'position ASC')),
			'{n}.PositionableElement.id',
			'{n}.PositionableElement.position'
		);

		$expectedOrder = array(
			'positionable-element-1' => 1,
			'positionable-element-2' => 2,
		);
		$this->assertTrue($success);
		$this->assertEquals($expectedOrder, $newOrder);
	}

/**
 * Test the before/afterDelete callbacks
 */
	public function testDeleteCallbacks() {
		$this->PositionableItem = ClassRegistry::init('PositionableItem');
		$this->PositionableElement->delete('positionable-element-1');
		$PositionableElement = $this->PositionableElement->findById('positionable-element-2');
		$PositionableItem = $this->PositionableItem->findById('positionable-item-1');

		$this->assertEqual($PositionableElement['PositionableElement']['position'],1);
		$this->assertEqual($PositionableItem['PositionableItem']['position'],2);
	}

/**
 * Test the before validate callbacks (auto positioning)
 */
	public function testAutoPositionning() {
		$data = array('PositionableElement' => array('foreign_model_id' => 'foreign-model-1'));
		$this->PositionableElement->save($data);
		$PositionableElement = $this->PositionableElement->findById($this->PositionableElement->id);

		$this->assertEqual($PositionableElement['PositionableElement']['position'], 4);
	}

	public function testAutoPositionningOnCurrentlyPositionnedElement() {
		$data = $this->_record;
		$data['PositionableElement']['position'] = null;
		$positionOfElement2Before = $this->PositionableElement->field('position', array('id' => 'positionable-element-2'));

		$save = $this->PositionableElement->save($data);
		$positionOfElement1 = $this->PositionableElement->field('position', array('id' => 'positionable-element-1'));
		$positionOfElement2After = $this->PositionableElement->field('position', array('id' => 'positionable-element-2'));

		$this->assertEqual($positionOfElement2Before, 2);
		$this->assertEqual($positionOfElement1, 3);
		$this->assertEqual($positionOfElement2After, 1);
	}

	public function testAutoPositionningWith0AsPosition() {
		$data = array('PositionableElement' => array('foreign_model_id' => 'foreign-model-1', 'position' => 0));
		$this->PositionableElement->save($data);
		$PositionableElement = $this->PositionableElement->findById($this->PositionableElement->id);

		$this->assertEqual($PositionableElement['PositionableElement']['position'], 4);
	}

/**
 * Test the sortByPosition method
 *
 * @return void
 */
	public function testSortByPosition() {
		$fixture = new PositionableElementFixture();
		$riFixture = new PositionableItemFixture();

		$PositionableElements = array(
			array('PositionableItem' => $riFixture->records[0]),
			array('PositionableElement' => $fixture->records[1]),
			array('PositionableElement' => $fixture->records[0])
		);
		$expected = array(
			array('PositionableElement' => $fixture->records[0]),
			array('PositionableElement' => $fixture->records[1]),
			array('PositionableItem' => $riFixture->records[0])
		);
		$PositionableElements = $this->PositionableElement->sortByPosition($PositionableElements);
		$this->assertEqual($PositionableElements, $expected);
	}

/**
 * Test the callbacks on save from an associated model
 */
	public function testUseCallbacksOnSaveAssocciated() {
		$PositionableAssociated = ClassRegistry::init('PositionableAssociated');
		$data = array(
			'PositionableAssociated' => array('id' => 'foreign-model-1'),
			'PositionableElement' => array('data')
		);

		$save = $PositionableAssociated->saveAssociated($data);
		$this->assertTrue(!empty($save));
		$PositionableElement = $this->PositionableElement->findById($this->PositionableElement->id);

		$this->assertEqual($PositionableElement['PositionableElement']['position'], 4);
	}

/**
 * Test data alteration of behavior
 **/
	public function testDataModifiedByBeforeSave() {
		$this->PositionableItem->save(array(
			'id' => 'positionable-item-1',
			'content' => 'Hello !',
			'position' => 2
		));
		$result = $this->PositionableItem->find('first');

		$this->assertEqual($result['PositionableItem'], array(
			'id' => 'positionable-item-1',
			'foreign_model_id' => 'foreign-model-1',
			'content'  => 'Hello !',
			'position' => 2
		));
	}

/**
 * Test repairPositionning method
 */
	public function testRepairPositionning() {
		$update = $this->PositionableElement->updateAll(array('position' => 4));
		$positionnablesBefore = $this->_getPositionned('foreign-model-1');
		$positionnablesBeforePosition = Hash::extract($positionnablesBefore, '{n}.{s}.position');
		$positionnablesBeforeId = Hash::extract($positionnablesBefore, '{n}.{s}.id');

		$repair = $this->PositionableElement->repairPositionning('foreign-model-1');

		$positionnablesAfter = $this->_getPositionned('foreign-model-1');
		$positionnablesAfterPosition = Hash::extract($positionnablesAfter, '{n}.{s}.position');
		$positionnablesAfterId = Hash::extract($positionnablesAfter, '{n}.{s}.id');
		$expectedBefore = array(3, 4, 4);
		$expectedAfter = array(1, 2, 3);

		$this->assertTrue($update, 'Update is not successful');
		$this->assertTrue($repair, 'Repair is not successful');
		$this->assertEquals($positionnablesBeforeId, $positionnablesAfterId, 'The order is not kept');
		$this->assertEquals($expectedBefore, $positionnablesBeforePosition, 'The position before repair is not as expected');
		$this->assertEquals($expectedAfter, $positionnablesAfterPosition, 'The position after repair is not as expected');
	}

	public function testRepairPositionningWithoutForeignKey() {
		$this->PositionableElement->id = 'positionable-element-2';
		$update = $this->PositionableElement->saveField('foreign_model_id', 'foreign-model-2');

		$repair = $this->PositionableElement->repairPositionning();

		$positionnablesFK1 = $this->_getPositionned('foreign-model-1');
		$positionnablesFK1Position = Hash::extract($positionnablesFK1, '{n}.{s}.position');
		$positionnablesFK2 = $this->_getPositionned('foreign-model-2');
		$positionnablesFK2Position = Hash::extract($positionnablesFK2, '{n}.{s}.position');
		$expectedFK1 = array('1', '2');
		$expectedFK2 = array('1');

		$this->assertNotEmpty($update, 'Update is not successful');
		$this->assertTrue($repair, 'Repair is not successful');
		$this->assertEquals($expectedFK1, $positionnablesFK1Position, 'The position after repair for foreign-model-1 is not as expected');
		$this->assertEquals($expectedFK2, $positionnablesFK2Position, 'The position after repair for foreign-model-2 is not as expected');
	}

	public function testRepairPositionningWith0AsPosition() {
		$update = $this->PositionableElement->updateAll(array('position' => 0));

		$repair = $this->PositionableElement->repairPositionning();

		$positionnables = $this->_getPositionned('foreign-model-1');
		$positionnablesPosition = Hash::extract($positionnables, '{n}.{s}.position');
		$expected = array(0, 0, 1);

		$this->assertNotEmpty($update, 'Update is not successful');
		$this->assertTrue($repair, 'Repair is not successful');
		$this->assertEquals($expected, $positionnablesPosition, 'The position after repair for foreign-model-1 is not as expected');
	}

	public function testRepairPositionningWithNullAsForeignKey() {
		$update = $this->PositionableElement->updateAll(array('foreign_model_id' => null));
		$update = $update && $this->PositionableElement->updateAll(array('position' => 4));

		$repair = $this->PositionableElement->repairPositionning();

		$positionnables = $this->_getPositionned(null);
		$positionnablesPosition = Hash::extract($positionnables, '{n}.{s}.position');
		$expected = array(1, 2);

		$this->assertNotEmpty($update, 'Update is not successful');
		$this->assertTrue($repair, 'Repair is not successful');
		$this->assertEquals($expected, $positionnablesPosition, 'The position after repair for foreign-model-1 is not as expected');
	}

	public function _getPositionned($foreignKey) {
		$options = array('conditions' => array('foreign_model_id' => $foreignKey));
		$elements = $this->PositionableElement->find('all', $options);

		$items = $this->PositionableItem->find('all', $options);
		return $this->PositionableItem->sortByPosition(array_merge($elements, $items));
	}

/**
 * Asserts that data are valid given Model validation rules
 * Calls the Model::validate() method and asserts the result
 *
 * @param Model $Model Model being tested
 * @param array $data Data to validate
 * @return void
 */
	public function assertValid(Model $Model, $data) {
		$this->assertTrue($this->_validData($Model, $data));
	}

/**
 * Asserts that data are validation errors match an expected value when
 * validation given data for the Model
 * Calls the Model::validate() method and asserts validationErrors
 *
 * @param Model $Model Model being tested
 * @param array $data Data to validate
 * @param array $expectedErrors Expected errors keys
 * @return void
 */
	public function assertValidationErrors($Model, $data, $expectedErrors) {
		$this->_validData($Model, $data, $validationErrors);
		sort($expectedErrors);
		$this->assertEqual(array_keys($validationErrors), $expectedErrors);
	}

/**
 * Convenience method allowing to validate data and return the result
 *
 * @param Model $Model Model being tested
 * @param array $data Profile data
 * @param array $validationErrors Validation errors: this variable will be updated with validationErrors (sorted by key) in case of validation fail
 * @return boolean Return value of Model::validate()
 */
	protected function _validData(Model $Model, $data, &$validationErrors = array()) {
		$valid = true;
		$Model->create($data);
		if (!$Model->validates()) {
			$validationErrors = $Model->validationErrors;
			ksort($validationErrors);
			$valid = false;
		} else {
			$validationErrors = array();
		}
		return $valid;
	}
}
