<?php
/**
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */
namespace Magento\Tax\Model\TaxClass;

use Magento\Framework\Exception\InputException;
use Magento\Tax\Api\Data\TaxClassDataBuilder;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel as TaxClassModel;
use Magento\TestFramework\Helper\Bootstrap;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Repository
     */
    private $taxClassRepository;

    /**
     * @var TaxClassDataBuilder
     */
    private $taxClassBuilder;

    /**
     * @var TaxClassModel
     */
    private $taxClassModel;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $predefinedTaxClasses;

    const SAMPLE_TAX_CLASS_NAME = 'Wholesale Customer';

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->taxClassRepository = $this->objectManager->create('Magento\Tax\Api\TaxClassRepositoryInterface');
        $this->taxClassBuilder = $this->objectManager->create('Magento\Tax\Api\Data\TaxClassDataBuilder');
        $this->taxClassModel = $this->objectManager->create('Magento\Tax\Model\ClassModel');
        $this->predefinedTaxClasses = [
            TaxClassManagementInterface::TYPE_PRODUCT => 'Taxable Goods',
            TaxClassManagementInterface::TYPE_CUSTOMER => 'Retail Customer',
        ];
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSave()
    {
        $taxClassDataObject = $this->taxClassBuilder
            ->setClassName(self::SAMPLE_TAX_CLASS_NAME)
            ->setClassType(TaxClassManagementInterface::TYPE_CUSTOMER)
            ->create();
        $taxClassId = $this->taxClassRepository->save($taxClassDataObject);
        $this->assertEquals(self::SAMPLE_TAX_CLASS_NAME, $this->taxClassModel->load($taxClassId)->getClassName());
    }

    /**
     * @magentoDbIsolation enabled
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage A class with the same name already exists for ClassType PRODUCT.
     */
    public function testSaveThrowsExceptionIfGivenTaxClassNameIsNotUnique()
    {
        //ClassType and name combination has to be unique.
        //Testing against existing Tax classes which are already setup when the instance is installed
        $taxClassDataObject = $this->taxClassBuilder
            ->setClassName($this->predefinedTaxClasses[TaxClassModel::TAX_CLASS_TYPE_PRODUCT])
            ->setClassType(TaxClassManagementInterface::TYPE_PRODUCT)
            ->create();
        $this->taxClassRepository->save($taxClassDataObject);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSaveThrowsExceptionIfGivenDataIsInvalid()
    {
        $taxClassDataObject = $this->taxClassBuilder->setClassName(null)
            ->setClassType('')
            ->create();
        try {
            $this->taxClassRepository->save($taxClassDataObject);
        } catch (InputException $e) {
            $errors = $e->getErrors();
            $this->assertEquals('class_name is a required field.', $errors[0]->getMessage());
            $this->assertEquals('class_type is a required field.', $errors[1]->getMessage());
        }
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testGet()
    {
        $taxClassName = 'Get Me';
        $taxClassDataObject = $this->taxClassBuilder
            ->setClassName($taxClassName)
            ->setClassType(TaxClassManagementInterface::TYPE_CUSTOMER)
            ->create();
        $taxClassId = $this->taxClassRepository->save($taxClassDataObject);
        $data = $this->taxClassRepository->get($taxClassId);
        $this->assertEquals($taxClassId, $data->getClassId());
        $this->assertEquals($taxClassName, $data->getClassName());
        $this->assertEquals(TaxClassManagementInterface::TYPE_CUSTOMER, $data->getClassType());
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage No such entity with class_id = -9999
     */
    public function testGetThrowsExceptionIfRequestedTaxClassDoesNotExist()
    {
        $this->taxClassRepository->get(-9999);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testDeleteById()
    {
        $taxClassName = 'Delete Me';
        $taxClassDataObject = $this->taxClassBuilder->setClassName($taxClassName)
            ->setClassType(TaxClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $taxClassId = $this->taxClassRepository->save($taxClassDataObject);

        $this->assertTrue($this->taxClassRepository->deleteById($taxClassId));

        // Verify if the tax class is deleted
        $this->setExpectedException(
            'Magento\Framework\Exception\NoSuchEntityException',
            "No such entity with class_id = $taxClassId"
        );
        $this->taxClassRepository->deleteById($taxClassId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage No such entity with class_id = 99999
     */
    public function testDeleteByIdThrowsExceptionIfTargetTaxClassDoesNotExist()
    {
        $nonexistentTaxClassId = 99999;
        $this->taxClassRepository->deleteById($nonexistentTaxClassId);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSaveWithExistingTaxClass()
    {
        $taxClassName = 'New Class Name';
        $taxClassDataObject = $this->taxClassBuilder->setClassName($taxClassName)
            ->setClassType(TaxClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $taxClassId = $this->taxClassRepository->save($taxClassDataObject);
        $this->assertEquals($taxClassName, $this->taxClassModel->load($taxClassId)->getClassName());

        $updatedTaxClassName = 'Updated Class Name';
        $taxClassDataObject = $this->taxClassBuilder->setClassName($updatedTaxClassName)
            ->setClassId($taxClassId)
            ->setClassType(TaxClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();

        $this->assertEquals($taxClassId, $this->taxClassRepository->save($taxClassDataObject));

        $this->assertEquals($updatedTaxClassName, $this->taxClassModel->load($taxClassId)->getClassName());
    }

    /**
     * @magentoDbIsolation enabled
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage Updating classType is not allowed.
     */
    public function testSaveThrowsExceptionIfTargetTaxClassHasDifferentClassType()
    {
        $taxClassName = 'New Class Name';
        $taxClassDataObject = $this->taxClassBuilder->setClassName($taxClassName)
            ->setClassType(TaxClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $taxClassId = $this->taxClassRepository->save($taxClassDataObject);
        $this->assertEquals($taxClassName, $this->taxClassModel->load($taxClassId)->getClassName());

        $updatedTaxClassName = 'Updated Class Name';
        $taxClassDataObject = $this->taxClassBuilder->setClassName($updatedTaxClassName)
            ->setClassId($taxClassId)
            ->setClassType(TaxClassModel::TAX_CLASS_TYPE_PRODUCT)
            ->create();

        $this->taxClassRepository->save($taxClassDataObject);
    }
}
