<?php
/***************************************************************************
 *   Copyright (C) 2006-2007 by Konstantin V. Arkhipov                     *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	/**
	 * @ingroup Types
	**/
	class ObjectType extends BasePropertyType
	{
		private $className = null;
		
		public function __construct($className)
		{
			$this->className = $className;
		}
		
		/**
		 * @return MetaClass
		**/
		public function getClass()
		{
			return MetaConfiguration::me()->getClassByName($this->className);
		}
		
		public function getClassName()
		{
			return $this->className;
		}
		
		public function getDeclaration()
		{
			return 'null';
		}
		
		public function isGeneric()
		{
			return false;
		}
		
		public function isMeasurable()
		{
			return false;
		}
		
		public function toMethods(
			MetaClass $class,
			MetaClassProperty $property,
			MetaClassProperty $holder = null
		)
		{
			return
				parent::toMethods($class, $property, $holder)
				.$this->toDropper($class, $property, $holder);
		}
		
		public function toGetter(
			MetaClass $class,
			MetaClassProperty $property,
			MetaClassProperty $holder = null
		)
		{
			$name = $property->getName();
			
			$methodName = 'get'.ucfirst($property->getName());
			
			$classHint = $property->getType()->getHint();
			
			if ($holder) {
				if ($property->getType() instanceof ObjectType) {
					$class = $property->getType()->getClassName();
				} else {
					$class = null;
				}
				
				return <<<EOT

/**
 * @return {$class}
**/
public function {$methodName}()
{
	return \$this->{$holder->getName()}->{$methodName}();
}

EOT;
			} else {
				if ($property->getRelationId() == MetaRelation::LAZY_ONE_TO_ONE) {
					$className = $property->getType()->getClassName();
					
					if ($property->isRequired()) {
						$method = <<<EOT
{$classHint}
public function {$methodName}()
{
	if (!\$this->{$name}) {
		\$this->{$name} = {$className}::dao()->getById(\$this->{$name}Id);
	}

	return \$this->{$name};
}

EOT;
					} else {
						$method = <<<EOT

{$classHint}
public function {$methodName}()
{
	if (!\$this->{$name} && \$this->{$name}Id) {
		\$this->{$name} = {$className}::dao()->getById(\$this->{$name}Id);
	}
	
	return \$this->{$name};
}

EOT;
					}
					
					$method .= <<<EOT

public function {$methodName}Id()
{
	return \$this->{$name}Id;
}

EOT;
				} elseif (
					$property->getRelationId() == MetaRelation::ONE_TO_MANY
					|| $property->getRelationId() == MetaRelation::MANY_TO_MANY
				) {
						$name = $property->getName();
						$methodName = ucfirst($name);
						$remoteName = ucfirst($property->getName());
						
						$containerName = $class->getName().$remoteName.'DAO';
						
						$method = <<<EOT

/**
 * @return {$containerName}
**/
public function get{$methodName}(\$lazy = false)
{
	if (!\$this->{$name} || (\$this->{$name}->isLazy() != \$lazy)) {
		\$this->{$name} = new {$containerName}(\$this, \$lazy);
	}
	
	return \$this->{$name};
}

/**
 * @return {$class->getName()}
**/
public function fill{$methodName}(\$collection, \$lazy = false)
{
	if (!\$this->{$name} || (\$this->{$name}->isLazy() != \$lazy)) {
		\$this->{$name} = new {$containerName}(\$this, \$lazy);
		
		if (!\$this->id) {
			throw new WrongStateException(
				'i do not know which object i belong to'
			);
		}
		
		\$this->{$name}->replaceList(\$collection);
	}
	
	return \$this;
}

EOT;
				} else {
					$method = <<<EOT

{$classHint}
public function {$methodName}()
{
	return \$this->{$name};
}

EOT;
				}
			}
			
			return $method;
		}
		
		public function toSetter(
			MetaClass $class,
			MetaClassProperty $property,
			MetaClassProperty $holder = null
		)
		{
			if (
				$property->getRelationId() == MetaRelation::ONE_TO_MANY
				|| $property->getRelationId() == MetaRelation::MANY_TO_MANY
			) {
				// we don't need setter in such cases
				return null;
			}
			
			$name = $property->getName();
			$methodName = 'set'.ucfirst($name);
			$classHint = $this->getHint();
			
			if ($holder) {
				return <<<EOT

/**
 * @return {$holder->getClass()->getName()}
**/
public function {$methodName}({$property->getType()->getClassName()} \${$name})
{
	\$this->{$holder->getName()}->{$methodName}(\${$name});
	
	return \$this;
}

EOT;
			} else {
				if ($property->getRelationId() == MetaRelation::LAZY_ONE_TO_ONE) {
					$method = <<<EOT

{$classHint}
public function {$methodName}({$this->className} \${$name})
{
	\$this->{$name} = \${$name};
	\$this->{$name}Id = \${$name}->getId();

	return \$this;
}

{$classHint}
public function {$methodName}Id(\$id)
{
	\$this->{$name} = null;
	\$this->{$name}Id = \$id;

	return \$this;
}

EOT;
				} else {
					$method = <<<EOT

{$classHint}
public function {$methodName}({$this->className} \${$name})
{
	\$this->{$name} = \${$name};

	return \$this;
}

EOT;
				}
			}
			
			return $method;
		}
		
		public function toDropper(
			MetaClass $class,
			MetaClassProperty $property,
			MetaClassProperty $holder = null
		)
		{
			if (
				$property->getRelationId() == MetaRelation::ONE_TO_MANY
				|| $property->getRelationId() == MetaRelation::MANY_TO_MANY
			) {
				// we don't need dropper in such cases
				return null;
			}
			
			$name = $property->getName();
			$methodName = 'drop'.ucfirst($name);
			
			if ($holder) {
					$method = <<<EOT

/**
 * @return {$holder->getClass()->getName()}
**/
public function {$methodName}()
{
	\$this->{$holder->getName()}->{$methodName}();

	return \$this;
}

EOT;
			} else {
				if ($property->getRelationId() == MetaRelation::LAZY_ONE_TO_ONE) {
					$method = <<<EOT

/**
 * @return {$class->getName()}
**/
public function {$methodName}()
{
	\$this->{$name} = null;
	\$this->{$name}Id = null;

	return \$this;
}

EOT;
				} else {
					$method = <<<EOT

/**
 * @return {$class->getName()}
**/
public function {$methodName}()
{
	\$this->{$name} = null;

	return \$this;
}

EOT;
				}
			}
			
			return $method;
		}
		
		public function toPrimitive()
		{
			throw new UnsupportedMethodException();
		}
		
		public function toColumnType()
		{
			return $this->getClass()->getIdentifier()->getType()->toColumnType();
		}
		
		public function getHint()
		{
			return <<<EOT
/**
 * @return {$this->getClassName()}
**/
EOT;
		}
	}
?>