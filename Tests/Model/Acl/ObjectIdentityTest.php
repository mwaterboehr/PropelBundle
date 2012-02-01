<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Tests\Model\Acl;

use Criteria;

use Propel\PropelBundle\Model\Acl\AclClass;
use Propel\PropelBundle\Model\Acl\ObjectIdentity as ModelObjectIdentity;
use Propel\PropelBundle\Model\Acl\ObjectIdentityQuery;
use Propel\PropelBundle\Model\Acl\ObjectIdentityAncestorQuery;

use Propel\PropelBundle\Tests\Fixtures\Model\Book;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class ObjectIdentityTest extends TestCase
{
    public function testCompatibleDefaultImplementation()
    {
        $objIdenity = $this->createModelObjectIdentity(1);

        $ancestorEntries = ObjectIdentityAncestorQuery::create()->find($this->con);
        $this->assertEquals(1, count($ancestorEntries));
        $this->assertEquals($objIdenity->getId(), $ancestorEntries->getFirst()->getAncestorId());
        $this->assertEquals($objIdenity->getId(), $ancestorEntries->getFirst()->getObjectIdentityId());

        $anotherIdenity = $this->createModelObjectIdentity(2);

        $ancestorEntries = ObjectIdentityAncestorQuery::create()->orderByAncestorId(Criteria::ASC)->find($this->con);
        $this->assertEquals(2, count($ancestorEntries));
        $this->assertEquals($objIdenity->getId(), $ancestorEntries[0]->getAncestorId());
        $this->assertEquals($objIdenity->getId(), $ancestorEntries[0]->getObjectIdentityId());
        $this->assertEquals($anotherIdenity->getId(), $ancestorEntries[1]->getAncestorId());
        $this->assertEquals($anotherIdenity->getId(), $ancestorEntries[1]->getObjectIdentityId());
    }

    public function testTreeSimpleParent()
    {
        $parent = $this->createModelObjectIdentity(1);
        $obj = $this->createModelObjectIdentity(2);

        $this->assertTrue((bool) $obj->setObjectIdentityRelatedByParentObjectIdentityId($parent)->save($this->con));

        $entries = ObjectIdentityAncestorQuery::create()
            ->filterByObjectIdentityId($obj->getId())
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(2, count($entries));
        $this->assertEquals($obj->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[0]->getAncestorId());
        $this->assertEquals($obj->getId(), $entries[1]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[1]->getAncestorId());

        $this->assertTrue((bool) $obj->setObjectIdentityRelatedByParentObjectIdentityId(null)->save($this->con));

        $entries = ObjectIdentityAncestorQuery::create()
            ->filterByObjectIdentityId($obj->getId())
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(1, count($entries));
        $this->assertEquals($obj->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[0]->getAncestorId());
    }

    /**
     * @depends testTreeSimpleParent
     */
    public function testTreeAddParentChildHavingChild()
    {
        $parent = $this->createModelObjectIdentity(1);
        $obj = $this->createModelObjectIdentity(2);
        $child = $this->createModelObjectIdentity(3);

        $child->setObjectIdentityRelatedByParentObjectIdentityId($obj)->save($this->con);
        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parent)->save($this->con);

        $entries = ObjectIdentityAncestorQuery::create()
            ->orderByObjectIdentityId(Criteria::ASC)
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(6, count($entries));

        $this->assertEquals($parent->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[0]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[1]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[1]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[2]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[2]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[3]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[3]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[4]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[4]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[5]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[5]->getAncestorId());
    }

    /**
     * Tree splitted:
     *   1-2
     *   3-4-5
     *
     * Tree merged:
     *   1-2-3-4-5
     *
     * @depends testTreeAddParentChildHavingChild
     */
    public function testTreeAddParentChildHavingGrandchildrenAndParentHavingParent()
    {
        // Part I, before.
        $grandParent = $this->createModelObjectIdentity(1);
        $parent = $this->createModelObjectIdentity(2);

        $parent->setObjectIdentityRelatedByParentObjectIdentityId($grandParent)->save($this->con);

        // Part II, before.
        $obj = $this->createModelObjectIdentity(3);
        $child = $this->createModelObjectIdentity(4);
        $grandChild = $this->createModelObjectIdentity(5);

        $grandChild->setObjectIdentityRelatedByParentObjectIdentityId($child)->save($this->con);
        $child->setObjectIdentityRelatedByParentObjectIdentityId($obj)->save($this->con);

        // Verify "before"
        $entries = ObjectIdentityAncestorQuery::create()
            ->orderByObjectIdentityId(Criteria::ASC)
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(9, count($entries));

        $this->assertEquals($grandParent->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[0]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[1]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[1]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[2]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[2]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[3]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[3]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[4]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[4]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[5]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[5]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[6]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[6]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[7]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[7]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[8]->getObjectIdentityId());
        $this->assertEquals($grandChild->getId(), $entries[8]->getAncestorId());

        // Merge Trees
        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parent)->save($this->con);

        $entries = ObjectIdentityAncestorQuery::create()
            ->orderByObjectIdentityId(Criteria::ASC)
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(15, count($entries));

        $this->assertEquals($grandParent->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[0]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[1]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[1]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[2]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[2]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[3]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[3]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[4]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[4]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[5]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[5]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[6]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[6]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[7]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[7]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[8]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[8]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[9]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[9]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[10]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[10]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[11]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[11]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[12]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[12]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[13]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[13]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[14]->getObjectIdentityId());
        $this->assertEquals($grandChild->getId(), $entries[14]->getAncestorId());

        // Split Tree
        $obj->setObjectIdentityRelatedByParentObjectIdentityId(null)->save($this->con);

        $entries = ObjectIdentityAncestorQuery::create()
            ->orderByObjectIdentityId(Criteria::ASC)
            ->orderByAncestorId(Criteria::ASC)
            ->find($this->con)
        ;
        $this->assertEquals(9, count($entries));

        $this->assertEquals($grandParent->getId(), $entries[0]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[0]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[1]->getObjectIdentityId());
        $this->assertEquals($grandParent->getId(), $entries[1]->getAncestorId());

        $this->assertEquals($parent->getId(), $entries[2]->getObjectIdentityId());
        $this->assertEquals($parent->getId(), $entries[2]->getAncestorId());

        $this->assertEquals($obj->getId(), $entries[3]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[3]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[4]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[4]->getAncestorId());

        $this->assertEquals($child->getId(), $entries[5]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[5]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[6]->getObjectIdentityId());
        $this->assertEquals($obj->getId(), $entries[6]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[7]->getObjectIdentityId());
        $this->assertEquals($child->getId(), $entries[7]->getAncestorId());

        $this->assertEquals($grandChild->getId(), $entries[8]->getObjectIdentityId());
        $this->assertEquals($grandChild->getId(), $entries[8]->getAncestorId());
    }

    /**
     * @depends testTreeAddParentChildHavingChild
     */
    public function testDeleteRemovesGrandchildren()
    {
        $parent = $this->createModelObjectIdentity(1);
        $obj = $this->createModelObjectIdentity(2);
        $child = $this->createModelObjectIdentity(3);

        $child->setObjectIdentityRelatedByParentObjectIdentityId($obj)->save($this->con);
        $obj->setObjectIdentityRelatedByParentObjectIdentityId($parent)->save($this->con);

        $parent->delete($this->con);
        $this->assertEquals(0, count(ObjectIdentityQuery::create()->find($this->con)));
        $this->assertEquals(0, count(ObjectIdentityAncestorQuery::create()->find($this->con)));
    }

    /**
     * @return \Propel\PropelBundle\Model\Acl\ObjectIdentity
     */
    protected function createModelObjectIdentity($identifier)
    {
        $aclClass = $this->getAclClass();
        $objIdentity = new ModelObjectIdentity();

        $this->assertTrue((bool) $objIdentity
            ->setAclClass($aclClass)
            ->setIdentifier($identifier)
            ->save($this->con)
        );

        return $objIdentity;
    }

    protected function getAclClass()
    {
        return AclClass::fromAclObjectIdentity(new ObjectIdentity(1, 'Propel\PropelBundle\Tests\Fixtures\Model\Book'), $this->con);
    }
}