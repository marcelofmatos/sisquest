<?php
/*
 *
 * @(#) $Id: form_layout_vertical.php,v 1.10 2008/03/11 09:49:41 mlemos Exp $
 *
 */

class form_layout_vertical_class extends form_custom_class
{
	var $inputs=array();
	var $data=array();
	var $properties=array();
	var $header = '<table>';
	var $footer = '</table>';
	var $input_format = "<tr><td>{label}:</td><td>{input}&nbsp;<span id=\"mark_{id}\">{mark}</span></td></tr>\n";
	var $switched_position_input_format = "<tr><td align=\"right\">{input}</td><td>{label}&nbsp;<span id=\"mark_{id}\">{mark}</span></td></tr>\n";
	var $no_label_input_format = "<tr><td colspan=\"2\" align=\"center\">{input}</td></tr>\n";
	var $invalid_mark = '[X]';
	var $default_mark = '';
	var $server_validate = 0;

	Function AddInput(&$form, $arguments)
	{
		if(!IsSet($arguments['Inputs'])
		|| GetType($arguments['Inputs']) != 'array'
		|| count(($arguments['Inputs']))==0)
			return('it was not specified a valid list of inputs to layout');
		$this->inputs = $arguments['Inputs'];
		if(IsSet($arguments['Data']))
		{
			if(GetType($arguments['Data']) != 'array')
				return('it was not specified a valid list of data elements to layout');
			$this->data = $arguments['Data'];
		}
		if(IsSet($arguments['Properties']))
		{
			if(GetType($arguments['Properties']) != 'array')
				return('it was not specified a valid list of input properties');
			$this->properties = $arguments['Properties'];
		}
		if(IsSet($arguments['DefaultMark']))
			$this->default_mark = $arguments['DefaultMark'];
		if(IsSet($arguments['InvalidMark']))
		{
			if(strlen($arguments['InvalidMark'])==0)
				return('it was not specified a valid input invalid mark');
			$this->invalid_mark = $arguments['InvalidMark'];
		}
		if(IsSet($arguments['InputFormat']))
		{
			if(strlen($arguments['InputFormat'])==0)
				return('it was not specified a valid input format template');
			$this->input_format = $arguments['InputFormat'];
		}
		if(IsSet($arguments['SwitchedPositionInputFormat']))
		{
			if(strlen($arguments['SwitchedPositionInputFormat'])==0)
				return('it was not specified a valid switched position input format template');
			$this->switched_position_input_format = $arguments['SwitchedPositionInputFormat'];
		}
		if(IsSet($arguments['NoLabelInputFormat']))
		{
			if(strlen($arguments['NoLabelInputFormat'])==0)
				return('it was not specified a valid no label input format template');
			$this->no_label_input_format = $arguments['NoLabelInputFormat'];
		}
		if(IsSet($arguments['Header']))
		{
			if(strlen($arguments['Header'])==0)
				return('it was not specified a valid header template');
			$this->header = $arguments['Header'];
		}
		if(IsSet($arguments['Footer']))
		{
			if(strlen($arguments['Footer'])==0)
				return('it was not specified a valid footer template');
			$this->footer = $arguments['Footer'];
		}
		return('');
	}

	Function AddInputPart(&$form)
	{
		if(strlen($error = $form->AddDataPart($this->header)))
			return($error);
		$inputs = count($this->inputs);
		$valid_marks=array(
			'dynamicinput'=>array(
				'input'=>'input',
			),
			'dynamiclabel'=>array(
				'label'=>'input'
			),
			'dynamicdata'=>array(
				'mark'=>'mark',
				'id'=>'id'
			)
		);
		$parsed = $parsed_switched_position = $parsed_no_label = 0;
		for($i = 0; $i < $inputs; $i++)
		{
			$input = $this->inputs[$i];
			if(IsSet($this->properties[$input]['Visible'])
			&& !$this->properties[$input]['Visible'])
				continue;
			if(IsSet($this->data[$input]))
			{
				if(strlen(($error=$form->AddDataPart($this->data[$input]))))
					return($error);
				continue;
			}
			$read_only=(IsSet($this->properties[$input]['ReadOnly']) && $this->properties[$input]['ReadOnly']);
			$dynamic=array(
				'input'=>$input,
				'mark'=>(IsSet($form->Invalid[$input]) ? (IsSet($this->properties[$input]['InvalidMark']) ? $this->properties[$input]['InvalidMark'] : $this->invalid_mark) : (IsSet($this->properties[$input]['DefaultMark']) ? $this->properties[$input]['DefaultMark'] : $this->default_mark)),
				'id'=>$input
			);
			if(IsSet($this->properties[$input]['InputFormat']))
			{
				if(strlen($error = $this->ParseFormat($this->properties[$input]['InputFormat'], $valid_marks, $custom_data, $custom_marks))
				|| strlen($error = $this->AddFormattedDynamicPart($form, $custom_data, $custom_marks, 0, $read_only, $dynamic)))
					return($error);
			}
			else
			{
				UnSet($label);
				$form->GetInputProperty($input, 'LABEL', $label);
				if(IsSet($label))
				{
					if(IsSet($this->properties[$input]['SwitchedPosition'])
					&& $this->properties[$input]['SwitchedPosition'])
					{
						if(!$parsed_switched_position)
						{
							if(strlen($error = $this->ParseFormat($this->switched_position_input_format, $valid_marks, $data_switched_position, $marks_switched_position)))
								return($error);
							$parsed = 0;
						}
						if(strlen($error = $this->AddFormattedDynamicPart($form, $data_switched_position, $marks_switched_position, 0, $read_only, $dynamic)))
							return($error);
					}
					else
					{
						if(!$parsed)
						{
							if(strlen($error = $this->ParseFormat($this->input_format, $valid_marks, $data, $marks)))
								return($error);
							$parsed = 0;
						}
						if(strlen($error = $this->AddFormattedDynamicPart($form, $data, $marks, 0, $read_only, $dynamic)))
							return($error);
					}
				}
				else
				{
					if(!$parsed_no_label)
					{
						if(strlen($error = $this->ParseFormat($this->no_label_input_format, $valid_marks, $data_no_label, $marks_no_label)))
							return($error);
						$parsed_no_label = 1;
					}
					if(strlen($error = $this->AddFormattedDynamicPart($form, $data_no_label, $marks_no_label, 0, $read_only, $dynamic)))
						return($error);
				}
			}
		}
		return($form->AddDataPart($this->footer));
	}

	Function GetContainedInputs(&$form, $kind, &$contained)
	{
		$contained = array($this->input);
		$ti = count($this->inputs);
		for($i = 0; $i < $ti; ++$i)
		{
			$input = $this->inputs[$i];
			if(IsSet($this->data[$input]))
				continue;
			if(strlen($error = $form->GetContainedInputs($input, $kind, $input_contained)))
				return($error);
			$tc = count($input_contained);
			for($c = 0; $c < $tc; ++$c)
				$contained[] = $input_contained[$c];
		}
		return('');
	}
};

?>