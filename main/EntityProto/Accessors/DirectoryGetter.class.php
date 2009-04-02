<?php
/***************************************************************************
 *   Copyright (C) 2009 by Ivan Y. Khvostishkov                            *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	final class DirectoryGetter extends PrototypedGetter
	{
		public function get($name)
		{
			if (!isset($this->mapping[$name]))
				throw new WrongArgumentException(
					"knows nothing about property '{$name}'"
				);
			
			$primitive = $this->mapping[$name];

			$path = $this->object.'/'.$primitive->getName();

			if ($primitive instanceof PrimitiveFile)
				return $path;

			if (!file_exists($path))
				return null;

			if (
				$primitive instanceof PrimitiveForm
				&& !$primitive instanceof PrimitiveFormsList
			)
				return $path;

			if ($primitive instanceof PrimitiveFormsList) {
				$result = array();

				$subDirs = glob($path.'/*');

				if ($subDirs === false)
					throw new WrongStateException(
						'cannot read directory '.$path
					);

				foreach ($subDirs as $path)
					$result[basename($path)] = $path;

				return $result;
			}

			$result = file_get_contents($path);

			return $result;
		}
	}
?>