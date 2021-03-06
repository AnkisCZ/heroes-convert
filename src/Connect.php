<?php namespace Heroes\Convert;
/*
 * heroes-convert
 *
 * Convert HeroesDataParser to heroes-talents, for Heroes of the Storm
 * https://github.com/tattersoftware/heroes-convert
 * 
 */

require_once 'Base.php';

/**
 * Class Connect
 *
 * Connects talents to their abilities.
 */
class Connect extends Base
{
	/**
	 * Ability key to use for the link
	 *
	 * @var string
	 */
	public $linkKey = 'abilityId';
	
	/**
	 * Update each talent's abilityLinks to heroes-talent format
	 *
	 * @return $this
	 */
	public function run()
	{
		$abilities = $this->abilitiesByNameId();
		$this->updateAbilityLinks($abilities);
		
		return $this;
	}

	/**
	 * Get every ability indexed by nameId
	 *
	 * @return array
	 */
	protected function abilitiesByNameId(): array
	{
		$return = [];
		
		// Traverse heroes for each ability
		foreach ($this->heroes as $hyperlinkId => $hero)
		{
			foreach ($hero['abilities'] as $i => $ability)
			{
				// Remove duplicates rather than overwrite
				if (isset($return[$ability['nameId']]))
				{
					// WIP - this is causing Samuro to lose his trait!
					unset($this->heroes[$hyperlinkId]['abilities'][$i]);
				}
				else
				{
					$return[$ability['nameId']] = $ability;
				}
			}
		}
		
		return $return;
	}

	/**
	 * Traverse heroes for each talent and update its abilityLinks
	 *
	 * @param array  Array of indexed abilities
	 */
	protected function updateAbilityLinks(array $abilities)
	{
		// Traverse heroes for each talent
		foreach ($this->heroes as $hyperlinkId => $hero)
		{
			foreach ($hero['talents'] as $level => $talents)
			{
				foreach ($talents as $i => $talent)
				{
					$links = [];
					
					// Seed abilityId for active/passive talents
					$abilityId = in_array($talent['abilityType'], ['Active', 'Passive']) ? $hero['hyperlinkId'] . '|' . $talent['abilityType'] : null;
					
					if (! empty($talent['abilityTalentLinkIds']))
					{
						foreach ($talent['abilityTalentLinkIds'] as $nameId)
						{
							if (! isset($abilities[$nameId]))
							{
								$this->logMessage("Unable to match ability nameId '{$nameId}' for {$talent['nameId']}", 'info');

								continue;
							}

							// Set the talent's abilityId to the first matched ability link						
							$abilityId = $abilityId ?? $abilities[$nameId]['abilityId'] ?? null;
							$links[]   = $abilities[$nameId][$this->linkKey];
						}
					
						if (empty($links))
						{
							$this->logMessage("Talent {$talent['nameId']} ({$talent['uid']}) missing ability links for: " . implode(', ', $talent['abilityTalentLinkIds']), 'warning');
						}
						else
						{
							sort($links);
						}
							
						// Add the new links
						$this->heroes[$hyperlinkId]['talents'][$level][$i]['abilityLinks'] = $links;
					}
					
					// Set the abilityId
					$this->heroes[$hyperlinkId]['talents'][$level][$i]['abilityId'] = $abilityId;
				}
			}
		}
	}
}
