<?php namespace Pyro\Module\Streams_core\Core\Model\Collection;

use Pyro\Model\Collection\EloquentCollection;

class FieldCollection extends EloquentCollection
{
	/**
	 * By slug
	 * @var array
	 */
	protected $by_slug = null;

	/**
	 * The array of Types 
	 * @var array
	 */
	protected $types = array();

	public function  __construct($fields = array())
	{
		parent::__construct($fields);
		
		foreach ($fields as $field)
		{
			$this->by_slug[$field->field_slug] = $field;
		}
	}

	/**
	 * Find a field by slug
	 * @param  string $field_slug
	 * @return object
	 */
	public function findBySlug($field_slug = null)
	{
		return isset($this->by_slug[$field_slug]) ? $this->by_slug[$field_slug] : null;
	}

	/**
	 * Get field slugs
	 * @return array
	 */
	public function getFieldSlugs()
	{
		return array_values($this->lists('field_slug'));
	}

	/**
	 * Get field slugs
	 * @param  array  $columns
	 * @return array
	 */
	public function getFieldSlugsExclude(array $columns = array())
	{
		$all = array_merge($this->getStandardColumns(), $this->getFieldSlugs());

		return array_diff($all, $columns);
	}

	/**
	 * Get array indexed by slug
	 * @return array
	 */
	public function getArrayIndexedBySlug()
	{
		$fields = array();

		foreach ($this->items as $field)
		{
			$fields[$field->field_slug] = $field;
		}

		return $fields;
	}

	/**
	 * Get an array of field types
	 * @param  Pyro\Module\Streams_core\Core\Model\Entry $entry An optional entry to instantiate the field types
	 * @return array The array of field types
	 */
	public function getTypes($entry = null)
	{
		$types = array();

		foreach ($this->items as $field)
		{
			$types[$field->field_type] = $field->getType($entry);
		}

		return new FieldTypeCollection($types);
	}	
}
