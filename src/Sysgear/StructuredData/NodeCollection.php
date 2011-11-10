<?php

namespace Sysgear\StructuredData;

use Closure, Countable, IteratorAggregate, ArrayAccess, Serializable;

class NodeCollection extends NodeInterface implements Countable,
    IteratorAggregate, ArrayAccess, Serializable
{
    protected $collection = array();
    protected $metadata = array();

    public function __construct(array $collection = array(), $type = 'list')
    {
        parent::__construct($type);
        $this->collection = $collection;
    }

    /**
     * Set metadata.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setMetadata($name, $value)
    {
        $this->metadata[$name] = $value;
    }

    /**
     * Return node metadata.
     *
     * @return map
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets the PHP array representation of this node collection.
     *
     * @return array The PHP array representation of this node collection.
     */
    public function toArray()
    {
        return $this->collection;
    }

    /**
     * Sets the internal iterator to the first node in the collection and
     * returns this node.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function first()
    {
        return reset($this->collection);
    }

    /**
     * Sets the internal iterator to the last node in the collection and
     * returns this node.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function last()
    {
        return end($this->collection);
    }

    /**
     * Gets the current key/index at the current internal iterator position.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Moves the internal iterator position to the next node.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * Gets the node of the collection at the current internal iterator position.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Removes an node with a specific key/index from the collection.
     *
     * @param mixed $key
     * @return \Sysgear\StructuredData\NodeInterface The removed node or NULL, if no node exists for the given key.
     */
    public function remove($key)
    {
        if (isset($this->collection[$key])) {
            $removed = $this->collection[$key];
            unset($this->collection[$key]);

            return $removed;
        }

        return null;
    }

    /**
     * Removes the specified node from the collection, if it is found.
     *
     * @param NodeInterface $node The node to remove.
     * @return boolean TRUE if this collection contained the specified node, FALSE otherwise.
     */
    public function removeNode(NodeInterface $node)
    {
        $key = array_search($node, $this->collection, true);

        if ($key !== false) {
            unset($this->collection[$key]);

            return true;
        }

        return false;
    }

    /**
     * ArrayAccess implementation of offsetExists()
     *
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @see get()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    /**
     * ArrayAccess implementation of offsetUnset()
     *
     * @see remove()
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Checks whether the collection contains a specific key/index.
     *
     * @param mixed $key The key to check for.
     * @return boolean TRUE if the given key/index exists, FALSE otherwise.
     */
    public function containsKey($key)
    {
        return isset($this->collection[$key]);
    }

    /**
     * Checks whether the given node is contained in the collection. This means reference equality.
     *
     * @param NodeInterface $node
     * @return boolean TRUE if the given node is contained in the collection,
     *          FALSE otherwise.
     */
    public function contains(NodeInterface $node)
    {
        return in_array($node, $this->collection, true);
    }

    /**
     * Tests for the existance of an node that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE if the predicate is TRUE for at least one node, FALSE otherwise.
     */
    public function exists(Closure $p)
    {
        foreach ($this->collection as $key => $node)
            if ($p($key, $node)) return true;
        return false;
    }

    /**
     * Searches for a given node and, if found, returns the corresponding key/index
     * of that node.
     *
     * @param NodeInterface $node The node to search for.
     * @return mixed The key/index of the node or FALSE if the node was not found.
     */
    public function indexOf(NodeInterface $node)
    {
        return array_search($node, $this->collection, true);
    }

    /**
     * Gets the node with the given key/index.
     *
     * @param mixed $key The key.
     * @return \Sysgear\StructuredData\NodeInterface The node or NULL, if no node exists for the given key.
     */
    public function get($key)
    {
        if (isset($this->collection[$key])) {
            return $this->collection[$key];
        }
        return null;
    }

    /**
     * Gets all keys/indexes of the collection nodes.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->collection);
    }

    /**
     * Gets all nodes.
     *
     * @return array
     */
    public function getValues()
    {
        return array_values($this->collection);
    }

    /**
     * Returns the number of nodes in the collection.
     *
     * Implementation of the Countable interface.
     *
     * @return integer The number of nodes in the collection.
     */
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Adds/sets an node in the collection at the index / with the specified key.
     *
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     *
     * @param mixed $key
     * @param NodeInterface $node
     */
    public function set($key, NodeInterface $node)
    {
        $this->collection[$key] = $node;
    }

    /**
     * Adds an node to the collection.
     *
     * @param NodeInterface $node
     * @return boolean Always TRUE.
     */
    public function add(NodeInterface $node)
    {
        $this->collection[] = $node;
        return true;
    }

    /**
     * Checks whether the collection is empty.
     *
     * Note: This is preferrable over count() == 0.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        return ! $this->collection;
    }

    /**
     * Gets an iterator for iterating over the nodes in the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * Applies the given function to each node in the collection and returns
     * a new collection with the node returned by the function.
     *
     * @param Closure $func
     * @return Collection
     */
    public function map(Closure $func)
    {
        return new self(array_map($func, $this->collection), $this->type);
    }

    /**
     * Returns all the nodes of this collection that satisfy the predicate p.
     * The order of the nodes is preserved.
     *
     * @param Closure $p The predicate used for nodeing.
     * @return Collection A collection with the results of the node operation.
     */
    public function filter(Closure $p)
    {
        return new self(array_filter($this->collection, $p), $this->type);
    }

    /**
     * Applies the given predicate p to all nodes of this collection,
     * returning true, if the predicate yields true for all nodes.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE, if the predicate yields TRUE for all nodes, FALSE otherwise.
     */
    public function forAll(Closure $p)
    {
        foreach ($this->collection as $key => $node) {
            if ( ! $p($key, $node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /**
     * Clears the collection.
     */
    public function clear()
    {
        $this->collection = array();
    }

    /**
     * Extract a slice of $length nodes starting at position $offset from the Collection.
     *
     * If $length is null it returns all nodes from $offset to the end of the Collection.
     * Keys have to be preserved by this method. Calling this method will only return the
     * selected slice and NOT change the nodes contained in the collection slice is called on.
     *
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function slice($offset, $length = null)
    {
        return array_slice($this->collection, $offset, $length, true);
    }

    public function serialize()
    {
        return serialize($this->collection);
    }

    public function unserialize($data)
    {
        $this->collection = unserialize($data);
    }
}