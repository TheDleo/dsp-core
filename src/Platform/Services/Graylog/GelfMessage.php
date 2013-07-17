<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Platform\Services\Graylog;

use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;
use Platform\Interfaces\Graylog;
use Platform\Interfaces\GraylogLevels;

/**
 * GelfMessage
 * Encapsulation of a graylog packet
 */
class GelfMessage implements Graylog
{
	//**********************************************************************
	//* Members
	//**********************************************************************

	/**
	 * @var array The GELF message data
	 */
	protected $_data = null;
	/**
	 * @var array The read-only fields
	 */
	protected static $_readOnlyFields
		= array(
			'version',
			'host',
			'timestamp',
			'id',
			//	Don't allow the additional field _id and _key, it could override the MongoDB key field.
			'_id',
			'_key',
		);
	/**
	 * @var array GELF v1.0 standard fields {@link https://github.com/Graylog2/graylog2-docs/wiki/GELF}
	 */
	protected static $_standardFields
		= array(
			//	GELF spec version – "1.0" (string); MUST be set by client library.
			'version',
			//	the name of the host or application that sent this message (string); MUST be set by client library.
			'host',
			//	a short descriptive message (string); MUST be set by client library.
			'short_message',
			//	a long message that can i.e. contain a backtrace and environment variables (string); optional.
			'full_message',
			//	UNIX microsecond timestamp (decimal); SHOULD be set by client library.
			'timestamp',
			//	the level equal to the standard syslog levels (decimal); optional, default is 1 (ALERT).
			'level',
			//	(string or decimal) optional, MUST be set by server to GELF if empty.
			'facility',
			//	the line in a file that caused the error (decimal); optional.
			'line',
			//	the file (with path if you want) that caused the error (string); optional.
			'file',
		);

	//**********************************************************************
	//* Methods
	//**********************************************************************

	/**
	 * Constructor
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $data = null )
	{
		$_host = posix_uname();
		$_host = $_host['nodename'];

		$_time = time();
		$_file = trim( $_SERVER['SCRIPT_FILENAME'] );

		if ( 'cli' == PHP_SAPI && $_file{0} != '/' )
		{
			$_file = $_SERVER['PWD'] . '/' . $_file;
		}

		$this->_data = array(
			'version'   => static::GelfVersion,
			'host'      => FilterInput::server( 'HTTP_HOST' ) . ' (' . $_host . ')',
			'timestamp' => $_time,
			'level'     => Option::get( $data, 'level', static::DefaultLevel ),
			'facility'  => Option::get( $data, 'facility', static::DefaultFacility ),
			'file'      => $_file,
		);

		if ( !empty( $data ) )
		{
			$this->_validateData( $data );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return Message
	 */
	public static function create( array $data = null )
	{
		$_class = get_called_class();

		return new $_class( $data );
	}

	/**
	 * @param string $key   Name of field to update
	 * @param mixed  $value Value to update field with; null to unset
	 *
	 * @return \GelfMessage
	 */
	protected function _setData( $key, $value )
	{
		Option::set( $this->_data, $key, $value );

		return $this;
	}

	/**
	 * Static method for verifying that required GELF data fields exist
	 *
	 * @param array $data Associative array of GELF data
	 *
	 * @throws \InvalidArgumentException
	 * @return boolean True if all required fields are populated; else false
	 */
	protected function _validateData( $data )
	{
		foreach ( $data as $_key => $_value )
		{
			if ( in_array( $_key, static::$_readOnlyFields ) )
			{
				throw new \InvalidArgumentException( "Setting value of '{$_key}' is not permitted" );
			}

			if ( in_array( $_key, static::$_standardFields ) )
			{
				call_user_func( array( $this, 'set' . Inflector::tag( $_key ) ), $_value );
				continue;
			}

			//	Otherwise...
			$this->addAdditionalField( $_key, $_value );
		}

		return true;
	}

	/**
	 * @param string $key If specified, return value of specific field
	 *
	 * @return mixed Value of field, null if it doesn't exist; or data array
	 */
	public function getData( $key = null )
	{
		if ( null === $key )
		{
			return $this->_data;
		}

		return Option::get( $this->_data[$key] );
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->getData( 'version' );
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->getData( 'host' );
	}

	/**
	 * @return string
	 */
	public function getShortMessage()
	{
		return $this->getData( 'short_message' );
	}

	/**
	 * @param string $value A short descriptive message
	 *
	 * @return GelfMessage
	 */
	public function setShortMessage( $value )
	{
		return $this->_setData( 'short_message', $value );
	}

	/**
	 * @return string
	 */
	public function getFullMessage()
	{
		return $this->getData( 'full_message' );
	}

	/**
	 * @param string $value A long message that can i.e. contain a backtrace
	 *                      and environment variables; null to omit
	 *
	 * @return GelfMessage
	 */
	public function setFullMessage( $value )
	{
		return $this->_setData( 'full_message', $value );
	}

	/**
	 * @return integer
	 */
	public function getTimestamp()
	{
		return $this->getData( 'timestamp' );
	}

	/**
	 * @return integer
	 */
	public function getLevel()
	{
		return $this->getData( 'level' );
	}

	/**
	 * @param int $value The message level; null for default
	 *
	 * @throws \InvalidArgumentException
	 * @return GelfMessage
	 */
	public function setLevel( $value = self::DefaultLevel )
	{
		if ( !GraylogLevels::contains( $value ) )
		{
			throw new \InvalidArgumentException( 'The level "' . $value . '" is not valid.' );
		}

		return $this->_setData( 'level', $value );
	}

	/**
	 * @return string
	 */
	public function getFacility()
	{
		return $this->getData( 'facility' );
	}

	/**
	 * @param string $value Facility the message pertains to; null for default
	 *
	 * @return GelfMessage
	 */
	public function setFacility( $value )
	{
		return $this->_setData( 'facility', $value );
	}

	/**
	 * @return integer
	 */
	public function getLine()
	{
		return $this->getData( 'line' );
	}

	/**
	 * @param integer $value The line in the file to look at; null to omit
	 *
	 * @return GelfMessage
	 */
	public function setLine( $value )
	{
		return $this->_setData( 'line', $value );
	}

	/**
	 * @return string
	 */
	public function getFile()
	{
		return $this->getData( 'file' );
	}

	/**
	 * @param integer $value The file to look at; null to omit
	 *
	 * @return GelfMessage
	 */
	public function setFile( $value )
	{
		return $this->_setData( 'file', $value );
	}

	/**
	 * @param string $field The key of the additional field (w/o underscore)
	 *
	 * @return string
	 */
	public function getAdditionalField( $field )
	{
		if ( '_' != $field[0] )
		{
			$field = '_' . $field;
		}

		return $this->getData( $field );
	}

	/**
	 * @param string $field   The key of the additional field
	 * @param string $value   The value of the additional field; null to unset
	 *
	 * @throws InvalidArgumentException
	 * @return \GelfMessage
	 */
	public function addAdditionalField( $field, $value )
	{
		if ( '_' != $field[0] )
		{
			$field = '_' . $field;
		}

		if ( '_id' == $field || '_key' == $field )
		{
			throw new \InvalidArgumentException( 'Additional fields may not be called "_id" or "_key".' );
		}

		return $this->_setData( $field, $value );
	}
}
