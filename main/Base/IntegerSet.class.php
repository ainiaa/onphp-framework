<?php
/***************************************************************************
 *   Copyright (C) 2007 by Denis M. Gabaidulin                             *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/
/* $Id:$ */

	/**
	 * Integer's set.
	 * 
	 * @ingroup Helpers
	**/
	class IntegerSet extends Range
	{
		public static function create($min = null, $max = null)
		{
			return new IntegerSet($min, $max);
		}
		
		public function contains($value)
		{
			if (
				$this->getMin() <= $value
				&& $value <= $this->getMax()
			)
				return true;
			else
				return false;
		}
	}
?>