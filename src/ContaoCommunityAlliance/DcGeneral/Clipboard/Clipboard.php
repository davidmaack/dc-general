<?php

/**
 * This file is part of contao-community-alliance/dc-general.
 *
 * (c) 2013-2015 Contao Community Alliance.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dc-general
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2013-2015 Contao Community Alliance.
 * @license    https://github.com/contao-community-alliance/dc-general/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace ContaoCommunityAlliance\DcGeneral\Clipboard;

use ContaoCommunityAlliance\DcGeneral\Data\ModelIdInterface;

/**
 * Class Clipboard.
 *
 * Default implementation of the clipboard.
 */
class Clipboard implements ClipboardInterface
{
    /**
     * The item collection (indexed by clipboard ids).
     *
     * @var ItemInterface[]
     */
    private $items = array();

    /**
     * The item collection (indexed by model ids).
     *
     * @var ItemInterface[]
     */
    private $itemsByModelId = array();

    /**
     * {@inheritDoc}
     */
    public function loadFrom($objEnvironment)
    {
        $data = $objEnvironment->getSessionStorage()->get('CLIPBOARD');

        if ($data) {
            // FIXME use another serialisation method
            $this->items = unserialize(base64_decode($data));
            foreach ($this->items as $item) {
                if ($item->getModelId()) {
                    $this->itemsByModelId[$item->getClipboardId()] = $item;
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function saveTo($objEnvironment)
    {
        // FIXME use another serialisation method
        $data = base64_encode(serialize($this->items));
        $objEnvironment->getSessionStorage()->set('CLIPBOARD', $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function push(ItemInterface $item)
    {
        $clipboardId = $item->getClipboardId();

        $this->items[$clipboardId] = $item;

        if ($item->getModelId()) {
            $this->itemsByModelId[$item->getModelId()->getSerialized()] = $item;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(ItemInterface $item)
    {
        $clipboardId = $item->getClipboardId();

        unset($this->items[$clipboardId]);

        if ($item->getModelId()) {
            unset($this->itemsByModelId[$item->getModelId()->getSerialized()]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeById(ModelIdInterface $modelId)
    {
        $serializedId = $modelId->getSerialized();
        if (isset($this->itemsByModelId[$serializedId])) {
            unset($this->items[$this->itemsByModelId[$serializedId]->getClipboardId()]);
            unset($this->itemsByModelId[$serializedId]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeByClipboardId($clipboardId)
    {
        if (isset($this->items[$clipboardId])) {
            if ($modelId = $this->items[$clipboardId]->getModelId()) {
                unset($this->itemsByModelId[$modelId->getSerialized()]);
            }
            unset($this->items[$clipboardId]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(ItemInterface $item)
    {
        $clipboardId = $item->getClipboardId();

        if (!isset($this->items[$clipboardId])) {
            return false;
        }

        $existingItem = $this->items[$clipboardId];

        return $existingItem->equals($item);
    }

    /**
     * {@inheritDoc}
     */
    public function hasId(ModelIdInterface $modelId)
    {
        return isset($this->itemsByModelId[$modelId->getSerialized()]);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(FilterInterface $filter)
    {
        $items = array();

        foreach ($this->items as $item) {
            if ($filter->accepts($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(FilterInterface $filter)
    {
        foreach ($this->items as $item) {
            if ($filter->accepts($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isNotEmpty(FilterInterface $filter)
    {
        return !$this->isEmpty($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->items = array();

        return $this;
    }

    // ************************************************** DEPRECATED **************************************************
    /**
     * The ids contained.
     *
     * @var array
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    protected $arrIds = array();

    /**
     * The ids that will create a circular reference and therefore shall get ignored for pasting.
     *
     * @var array
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    protected $arrCircularIds = array();

    /**
     * The current mode the clipboard is in.
     *
     * @var string
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    protected $mode;

    /**
     * The id of the parent element for create mode.
     *
     * @var string
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    protected $parentId;

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function isCut()
    {
        return $this->mode == self::MODE_CUT;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function isCopy()
    {
        return $this->mode == self::MODE_COPY;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function isCreate()
    {
        return $this->mode == self::MODE_CREATE;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function copy($ids)
    {
        $this->mode = self::MODE_COPY;

        if (is_array($ids) || ($ids === null)) {
            $this->setContainedIds($ids);
        } else {
            $this->setContainedIds(array($ids));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function cut($ids)
    {
        $this->mode = self::MODE_CUT;

        if (is_array($ids) || ($ids === null)) {
            $this->setContainedIds($ids);
        } else {
            $this->setContainedIds(array($ids));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function create($parentId)
    {
        $this->mode = self::MODE_CREATE;

        $this->setContainedIds(array(null));
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function setContainedIds($arrIds)
    {
        $this->arrIds = $arrIds;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function getContainedIds()
    {
        return $this->arrIds;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function setCircularIds($arrIds)
    {
        $this->arrCircularIds = (array) $arrIds;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function getCircularIds()
    {
        return $this->arrCircularIds;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use new ModelIdInterface based methods.
     */
    public function getParent()
    {
        return $this->isCreate() ? $this->parentId : null;
    }
}
