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
/**
 * EmailTemplate.php
 * The system email template model for the DSP
 *
 * Columns:
 *
 * @property integer             $id
 * @property string              $name
 * @property string              $description
 * @property string              $to
 * @property string              $cc
 * @property string              $bcc
 * @property string              $subject
 * @property string              $body_text
 * @property string              $body_html
 * @property string              $from_name
 * @property string              $from_email
 * @property string              $reply_to_name
 * @property string              $reply_to_email
 * @property string              $defaults
 *
 * Relations:
 *
 */
class EmailTemplate extends BaseDspSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return Role the static model class
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return static::tableNamePrefix() . 'email_template';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$_rules = array(
			array( 'name', 'required' ),
			array( 'name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
			array( 'name', 'length', 'max' => 64 ),
			array( 'subject, from_name, reply_to_name', 'length', 'max' => 80 ),
			array( 'from_email, reply_to_email', 'length', 'max' => 255 ),
			array( 'description, to, cc, bcc, body_text, body_html, defaults', 'safe' ),
			array( 'id, name', 'safe', 'on' => 'search' ),
		);

		return array_merge( parent::rules(), $_rules );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array();

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels( $additionalLabels = array() )
	{
		return parent::attributeLabels(
			array_merge(
				$additionalLabels,
				array(
					 'name'           => 'Name',
					 'description'    => 'Description',
					 'to'             => 'To Email List',
					 'cc'             => 'CC Email List',
					 'bcc'            => 'BCC Email List',
					 'subject'        => 'Subject',
					 'body_text'      => 'Body Text Format',
					 'body_html'      => 'Body HTML Format',
					 'from_name'      => 'From Name',
					 'from_email'     => 'From Email',
					 'reply_to_name'  => 'Reply To Name',
					 'reply_to_email' => 'Reply To Email',
					 'defaults'       => 'Default Values',
				)
			)
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search( $criteria = null )
	{
		$_criteria = $criteria ? : new \CDbCriteria;

		$_criteria->compare( 'name', $this->name, true );
		$_criteria->compare( 'subject', $this->subject );

		return parent::search( $_criteria );
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeSave()
	{
		if ( is_array( $this->defaults ) )
		{
			$this->defaults = json_encode( $this->defaults );
		}

		return parent::beforeSave();
	}

	/**
	 * {@InheritDoc}
	 */
	public function afterFind()
	{
		if ( isset( $this->defaults ) )
		{
			$this->defaults = json_decode( $this->defaults, true );
		}
		else
		{
			$this->defaults = array();
		}

		parent::afterFind();
	}

	/**
	 * @param string $requested
	 * @param array  $columns
	 * @param array  $hidden
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested, $columns = array(), $hidden = array() )
	{
		return parent::getRetrievableAttributes(
			$requested,
			array_merge(
				array(
					 'name',
					 'description',
					 'to',
					 'cc',
					 'bcc',
					 'subject',
					 'body_text',
					 'body_html',
					 'from_name',
					 'from_email',
					 'reply_to_name',
					 'reply_to_email',
					 'defaults',
				),
				$columns
			),
			$hidden
		);
	}
}