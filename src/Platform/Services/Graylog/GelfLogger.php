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

use Kisma\Core\SeedBag;
use Platform\Interfaces\Graylog;

/**
 * GelfLogger
 */
class GelfLogger extends SeedBag implements Graylog
{
	//**********************************************************************
	//* Methods
	//**********************************************************************

	/**
	 * Logs a message
	 *
	 * @param array|GelfMessage $message The GELF message to log
	 *
	 * @return bool
	 */
	public static function logMessage( $message )
	{
		if ( !( $message instanceof GelfMessage ) )
		{
			$_message = new GelfMessage( $message );
			$_data = $_message->getData();
		}
		else
		{
			$_data = $message->getData();
		}

		$_toSend = static::_prepareData( $_data );

		if ( !$_toSend )
		{
			return false;
		}

		$_url = 'udp://' . static::DefaultHost . ':' . static::DefaultPort;
		$_sock = stream_socket_client( $_url );

		foreach ( $_toSend as $_buf )
		{
			if ( !fwrite( $_sock, $_buf ) )
			{
				return false;
			}
		}

		return true;
	}

	//**********************************************************************
	//* Protected Methods
	//**********************************************************************

	/**
	 * Prepare GELF data to be written to UDP socket
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected static function _prepareData( $data )
	{
		if ( false === ( $_jsonData = json_encode( $data ) ) )
		{
			return false;
		}

		$_gzJsonData = gzcompress( $_jsonData );

		if ( $_gzJsonData === false )
		{
			return false;
		}

		if ( strlen( $_gzJsonData ) <= static::MaximumChunkSize )
		{
			return array( $_gzJsonData );
		}

		$_prepared = array();

		$_chunks = str_split( $_gzJsonData, static::MaximumChunkSize );
		$_numChunks = count( $_chunks );

		if ( $_numChunks > static::MaximumChunksAllowed )
		{
			return false;
		}

		$_msgId = hash( 'sha256', microtime( true ) . rand( 10000, 99999 ), true );
		$_seqNum = 0;

		foreach ( $_chunks as $_chunk )
		{
			$_prepared[] = static::_prepareChunk( $_chunk, $_msgId, $_seqNum, $_numChunks );
		}

		return $_prepared;
	}

	/**
	 * Pack a chunk of GELF data
	 *
	 * @param string  $chunk  The chunk of gzipped JSON GELF data to prepare
	 * @param string  $msgId  The 8-byte message id, same for entire chunk set
	 * @param integer $seqNum The sequence number of the chunk (0-$seqCnt)
	 * @param integer $seqCnt The total number of chunks in the sequence
	 *
	 * @return string A packed chunk ready to write to the UDP socket
	 */
	protected static function _prepareChunk( $chunk, $msgId, $seqNum, $seqCnt )
	{
		return pack( 'CC', 30, 15 ) . $msgId . pack( 'nn', $seqNum, $seqCnt ) . $chunk;
	}
}
