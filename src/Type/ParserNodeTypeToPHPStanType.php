<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Constant\ConstantBooleanType;

class ParserNodeTypeToPHPStanType
{

	/**
	 * @param \PhpParser\Node\Name|\PhpParser\Node\Identifier|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType|null $type
	 * @param ClassReflection|null $classReflection
	 * @return Type
	 */
	public static function resolve($type, ?ClassReflection $classReflection): Type
	{
		if ($type === null) {
			return new MixedType();
		} elseif ($type instanceof Name) {
			$typeClassName = (string) $type;
			$lowercasedClassName = strtolower($typeClassName);
			if ($classReflection !== null && in_array($lowercasedClassName, ['self', 'static'], true)) {
				$typeClassName = $classReflection->getName();
			} elseif (
				$lowercasedClassName === 'parent'
				&& $classReflection !== null
				&& $classReflection->getParentClass() !== false
			) {
				$typeClassName = $classReflection->getParentClass()->getName();
			}

			if ($lowercasedClassName === 'static') {
				return new StaticType($typeClassName);
			}

			return new ObjectType($typeClassName);
		} elseif ($type instanceof NullableType) {
			return TypeCombinator::addNull(self::resolve($type->type, $classReflection));
		} elseif ($type instanceof \PhpParser\Node\UnionType) {
			$types = [];
			foreach ($type->types as $unionTypeType) {
				$types[] = self::resolve($unionTypeType, $classReflection);
			}

			return TypeCombinator::union(...$types);
		}

		$type = $type->name;
		if ($type === 'string') {
			return new StringType();
		} elseif ($type === 'int') {
			return new IntegerType();
		} elseif ($type === 'bool') {
			return new BooleanType();
		} elseif ($type === 'float') {
			return new FloatType();
		} elseif ($type === 'callable') {
			return new CallableType();
		} elseif ($type === 'array') {
			return new ArrayType(new MixedType(), new MixedType());
		} elseif ($type === 'iterable') {
			return new IterableType(new MixedType(), new MixedType());
		} elseif ($type === 'void') {
			return new VoidType();
		} elseif ($type === 'object') {
			return new ObjectWithoutClassType();
		} elseif ($type === 'false') {
			return new ConstantBooleanType(false);
		} elseif ($type === 'null') {
			return new NullType();
		} elseif ($type === 'mixed') {
			return new MixedType(true);
		}

		return new MixedType();
	}

}
