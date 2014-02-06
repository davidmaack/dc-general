<?php
/**
 * PHP version 5
 * @package    generalDriver
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace DcGeneral\Contao\View\Contao2BackendView;

use DcGeneral\Data\CollectionInterface;
use DcGeneral\Data\DCGE;
use DcGeneral\Data\ModelInterface;
use DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use DcGeneral\DataDefinition\Definition\View\ListingConfigInterface;

/**
 * Class ListView.
 *
 * The implementation of a plain listing view.
 *
 * @package DcGeneral\Contao\View\Contao2BackendView
 */
class ListView extends BaseView
{
	/**
	 * Load the collection of child items and the parent item for the currently selected parent item.
	 *
	 * @return CollectionInterface
	 */
	public function loadCollection()
	{
		$environment = $this->getEnvironment();
		$definition  = $environment->getDataDefinition();
		$backendView = $this->getViewSection();

		/** @var Contao2BackendViewDefinitionInterface $backendView */
		$listingConfig = $backendView->getListingConfig();

		$objCurrentDataProvider = $environment->getDataProvider();
		$objParentDataProvider  = $environment->getDataProvider($definition->getBasicDefinition()->getParentDataProvider());
		$objConfig              = $environment->getController()->getBaseConfig();

		// Initialize sorting.
		$objConfig->setSorting($listingConfig->getDefaultSortingFields());

		$this->getPanel()->initialize($objConfig);

		$objCollection = $objCurrentDataProvider->fetchAll($objConfig);

		// If we want to group the elements, do so now.
		if (isset($objCondition)
			&& ($this->getViewSection()->getListingConfig()->getGroupingMode() == ListingConfigInterface::GROUP_CHAR)
		)
		{
			foreach ($objCollection as $objModel)
			{
				/** @var ModelInterface $objModel */
				$arrFilter = $objCondition->getInverseFilter($objModel);
				$objConfig = $objParentDataProvider->getEmptyConfig()->setFilter($arrFilter);
				$objParent = $objParentDataProvider->fetch($objConfig);

				// TODO: wouldn't it be wiser to link the model instance instead of the id of the parenting model?
				$objModel->setMeta(DCGE::MODEL_PID, $objParent->getId());
			}
		}

		return $objCollection;
	}

	/**
	 * Return the table heading.
	 *
	 * @return array
	 */
	protected function getTableHead()
	{
		$arrTableHead      = array();
		$definition        = $this->getEnvironment()->getDataDefinition();
		$properties        = $definition->getPropertiesDefinition();
		$viewDefinition    = $this->getViewSection();
		$listingDefinition = $viewDefinition->getListingConfig();

		// Generate the table header if the "show columns" option is active.
		if ($listingDefinition->getShowColumns())
		{
			foreach ($properties->getPropertyNames() as $f)
			{
				$property = $properties->getProperty($f);
				if ($property)
				{
					$label = $property->getLabel();
				}
				else
				{
					$label = $f;
				}

				$arrTableHead[] = array(
					// FIXME: getAdditionalSorting() unimplemented
					'class'   => 'tl_folder_tlist col_'
					/* . $f . ((in_array($f, $definition->getAdditionalSorting())) ? ' ordered_by' : '') */,
					'content' => $label[0]
				);
			}

			$arrTableHead[] = array(
				'class'   => 'tl_folder_tlist tl_right_nowrap',
				'content' => '&nbsp;'
			);
		}

		return $arrTableHead;
	}

	/**
	 * Set label for list view.
	 *
	 * @param CollectionInterface $collection          The collection containing the models.
	 *
	 * @param array               $groupingInformation The grouping information as retrieved via
	 *                                                 BaseView::getGroupingMode().
	 *
	 * @return void
	 */
	protected function setListViewLabel($collection, $groupingInformation)
	{
		$viewDefinition = $this->getViewSection();
		$listingConfig  = $viewDefinition->getListingConfig();
		$remoteCur      = null;
		$groupClass     = 'tl_folder_tlist';
		$eoCount        = -1;

		foreach ($collection as $objModelRow)
		{
			/** @var \DcGeneral\Data\ModelInterface $objModelRow */

			// Build the sorting groups.
			if ($groupingInformation)
			{
				$remoteNew = $this->formatCurrentValue(
					$groupingInformation['property'],
					$objModelRow,
					$groupingInformation['mode'],
					$groupingInformation['length']
				);

				// Add the group header if it differs from the last header.
				if (!$listingConfig->getShowColumns()
					&& ($groupingInformation['mode'] !== ListingConfigInterface::GROUP_NONE)
					&& (($remoteNew != $remoteCur) || ($remoteCur === null))
				)
				{
					$eoCount = -1;

					$objModelRow->setMeta(DCGE::MODEL_GROUP_VALUE, array(
						'class' => $groupClass,
						'value' => $remoteNew
					));

					$groupClass = 'tl_folder_list';
					$remoteCur  = $remoteNew;
				}
			}

			$objModelRow->setMeta(DCGE::MODEL_EVEN_ODD_CLASS, (((++$eoCount) % 2 == 0) ? 'even' : 'odd'));

			$objModelRow->setMeta(DCGE::MODEL_LABEL_VALUE, $this->formatModel($objModelRow));
		}
	}

	/**
	 * Generate list view from current collection.
	 *
	 * @param CollectionInterface $collection The collection containing the models.
	 *
	 * @return string
	 */
	protected function viewList($collection)
	{
		$environment = $this->getEnvironment();
		$definition  = $environment->getDataDefinition();

		$groupingInformation = $this->getGroupingMode();

		// Set label.
		$this->setListViewLabel($collection, $groupingInformation);

		// Generate buttons.
		foreach ($collection as $i => $objModel)
		{
			// Regular buttons - only if not in select mode!
			if (!$this->isSelectModeActive())
			{
				$previous = ((!is_null($collection->get($i - 1))) ? $collection->get($i - 1) : null);
				$next     = ((!is_null($collection->get($i + 1))) ? $collection->get($i + 1) : null);
				/** @var \DcGeneral\Data\ModelInterface $objModel */
				$objModel->setMeta(
					DCGE::MODEL_BUTTONS,
					$this->generateButtons(
						$objModel,
						$definition->getName(),
						null, // $environment->getRootIds(),
						false,
						null,
						$previous,
						$next
					)
				);
			}
		}

		// Add template.
		if ($groupingInformation['mode'] != ListingConfigInterface::GROUP_NONE)
		{
			$objTemplate = $this->getTemplate('dcbe_general_grouping');
		}
		elseif ($groupingInformation['property'] != '')
		{
			$objTemplate = $this->getTemplate('dcbe_general_listView_sorting');
		}
		else
		{
			$objTemplate = $this->getTemplate('dcbe_general_listView');
		}

		$this
			->addToTemplate('tableName', strlen($definition->getName()) ? $definition->getName() : 'none', $objTemplate)
			->addToTemplate('collection', $collection, $objTemplate)
			->addToTemplate('select', $this->getEnvironment()->getInputProvider()->getParameter('act'), $objTemplate)
			->addToTemplate('action', ampersand(\Environment::getInstance()->request, true), $objTemplate)
			->addToTemplate('mode', ($groupingInformation ? $groupingInformation['mode'] : null), $objTemplate)
			->addToTemplate('tableHead', $this->getTableHead(), $objTemplate)
			// Set dataprovider from current and parent.
			->addToTemplate('pdp', '', $objTemplate)
			->addToTemplate('cdp', $definition->getName(), $objTemplate)
			->addToTemplate('selectButtons', $this->getSelectButtons(), $objTemplate);

		// Add breadcrumb, if we have one.
		$strBreadcrumb = $this->breadcrumb();
		if ($strBreadcrumb != null)
		{
			$this->addToTemplate('breadcrumb', $strBreadcrumb, $objTemplate);
		}

		return $objTemplate->parse();
	}


	/**
	 * Copy mode - this redirects to edit.
	 *
	 * @return string
	 */
	public function copy()
	{
		return $this->edit();
	}

	/**
	 * Show all entries from one table.
	 *
	 * @return string
	 */
	public function showAll()
	{
		$this->checkClipboard();
		$collection = $this->loadCollection();

		$arrReturn            = array();
		$arrReturn['panel']   = $this->panel();
		$arrReturn['buttons'] = $this->generateHeaderButtons('tl_buttons_a');
		$arrReturn['body']    = $this->viewList($collection);

		// Return all.
		return implode("\n", $arrReturn);
	}
}
