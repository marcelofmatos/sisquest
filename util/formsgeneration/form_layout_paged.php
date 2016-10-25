<?php
/*
 *
 * @(#) $Id: form_layout_paged.php,v 1.11 2008/03/13 09:20:03 mlemos Exp $
 *
 */

class form_layout_paged_class extends form_custom_class
{
	var $pages = array();
	var $current_page = '';
	var $styles = array();
	var $side = 'top';
	var $border_width = 1;
	var $page_border_width = 2;
	var $border_radius=8;
	var $border_color = '';
	var $foreground_color = '';
	var $background_color = '';
	var $lighter_border_color = '#eeeeee';
	var $darker_border_color = '#777777';
	var $color_offset = 50;
	var $tab_padding = 2;
	var $tab = '';
	var $button = '';
	var $page = '';
	var $switch = '';
	var $class = '';
	var $page_class = '';
	var $tab_class = '';
	var $gap_class = '';
	var $page_button_class = '';
	var $tab_button_class = '';
	var $fade_pages_time = 0;
	var $contained = array();

	Function ColorChangeIntensity($color,$intensity_offset)
	{
		if(preg_match('/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/', $color, $components) != 7)
			return($color);
		if(($red = intval(HexDec($components[1]) * (100 + $intensity_offset) / 100)) > 255)
			$red = 255;
		if(($green = intval(HexDec($components[2]) * (100 + $intensity_offset) / 100)) > 255)
			$green = 255;
		if(($blue = intval(HexDec($components[3]) * (100 + $intensity_offset) / 100)) > 255)
			$blue = 255;
		return(sprintf('#%02X%02X%02X', $red, $green, $blue));
	}

	Function SetStyles()
	{
		$border=' border-width: '.strval($this->border_width).'px ;';
		$page_border=' border-width: '.strval($this->page_border_width).'px ;';
		$border_color=(strlen($this->border_color) ? $this->border_color : (strlen($this->foreground_color) ? $this->foreground_color : (strlen($this->background_color) ? $this->background_color : '')));
		$lighter=(strlen($this->lighter_border_color) ? $this->lighter_border_color : (strlen($border_color) ? $this->ColorChangeIntensity($border_color, $this->color_offset) : ''));
		$darker=(strlen($this->darker_border_color) ? $this->darker_border_color : (strlen($border_color) ? $this->ColorChangeIntensity($border_color, -$this->color_offset) : ''));
		$nowrap=' white-space: nowrap ;';
		switch($this->side)
		{
			case 'bottom':
				$page_style=' border-bottom-style: solid;  border-bottom-color: '.$darker.'; border-top-style: none; border-left-style: solid; border-left-color: '.$lighter.'; border-right-style: solid; border-right-color: '.$darker.'; border-bottom-left-radius: {BORDERRADIUS} ; border-bottom-right-radius: {BORDERRADIUS} ; -moz-border-radius-bottomright: {BORDERRADIUS} ; -moz-border-radius-bottomleft: {BORDERRADIUS} ; -webkit-border-bottom-right-radius: {BORDERRADIUS} ; -webkit-border-bottom-left-radius: {BORDERRADIUS}';
				$tab_style=' border-style: solid;  border-bottom-color: '.$darker.'; border-top-color: '.$darker.'; border-left-color: '.$lighter.'; border-right-color: '.$darker.'; border-bottom-left-radius: {BORDERRADIUS} ; border-bottom-right-radius: {BORDERRADIUS} ; -moz-border-radius-bottomright: {BORDERRADIUS} ; -moz-border-radius-bottomleft: {BORDERRADIUS} ; -webkit-border-bottom-right-radius: {BORDERRADIUS} ; -webkit-border-bottom-left-radius: {BORDERRADIUS}';
				$gap_style=' padding: 0px ; border-bottom-style: none; border-top-style: solid; border-top-color: '.$darker.'; border-left-style: none; border-right-style: none';
				break;
			case 'top':
			default:
				$page_style=' border-top-style: solid; border-top-color: '.$lighter.'; border-bottom-style: none; border-left-style: solid; border-left-color: '.$lighter.'; border-right-style: solid; border-right-color: '.$darker.'; border-top-left-radius: {BORDERRADIUS} ; border-top-right-radius: {BORDERRADIUS} ; -moz-border-radius-topright: {BORDERRADIUS} ; -moz-border-radius-topleft: {BORDERRADIUS} ; -webkit-border-top-right-radius: {BORDERRADIUS} ; -webkit-border-top-left-radius: {BORDERRADIUS}';
				$tab_style=' border-style: solid; border-top-color: '.$lighter.'; border-bottom-color: '.$lighter.'; border-left-color: '.$lighter.'; border-right-color: '.$darker.'; border-top-left-radius: {BORDERRADIUS} ; border-top-right-radius: {BORDERRADIUS} ; -moz-border-radius-topright: {BORDERRADIUS} ; -moz-border-radius-topleft: {BORDERRADIUS} ; -webkit-border-top-right-radius: {BORDERRADIUS} ; -webkit-border-top-left-radius: {BORDERRADIUS}';
				$gap_style=' padding: 0px ; border-top-style: none; border-bottom-style: solid; border-bottom-color: '.$lighter.'; border-left-style: none; border-right-style: none';
				break;
		}
		$this->styles=array(
			'page'=>$page_border.$nowrap.str_replace('{BORDERRADIUS}',$this->border_radius.'px',$page_style),
			'tab'=>$border.$nowrap.str_replace('{BORDERRADIUS}',$this->border_radius.'px',$tab_style),
			'gap'=>$border.$nowrap.$gap_style,
			'page_button'=>'border-width: 0px; font-weight: bold',
			'tab_button'=>'border-width: 0px'
		);
	}

	Function AddInput(&$form, $arguments)
	{
		if(!IsSet($arguments['Pages'])
		|| GetType($arguments['Pages']) != 'array'
		|| count(($arguments['Pages']))==0)
			return('it was not specified a valid list of pages to layout');
		$this->pages = $arguments['Pages'];
		Reset($this->pages);
		$this->current_page = Key($this->pages);
		$this->tab = $this->GenerateInputID($form, $this->input, 'tab');
		$this->button = $this->GenerateInputID($form, $this->input, 'button');
		$this->page = $this->GenerateInputID($form, $this->input, 'page');
		$this->switch = $this->GenerateInputID($form, $this->input, 'switch');
		$this->class = $this->GenerateInputID($form, $this->input, 'class');
		if(strlen($error = $form->AddInput(array(
			'TYPE'=>'hidden',
			'ID'=>$this->page,
			'NAME'=>$this->page,
			'VALUE'=>$this->current_page,
		))))
			return($error);
		if(IsSet($arguments['PageClass']))
			$this->page_class = $arguments['PageClass'];
		if(IsSet($arguments['TabClass']))
			$this->tab_class = $arguments['TabClass'];
		if(IsSet($arguments['GapClass']))
			$this->gap_class = $arguments['GapClass'];
		if(IsSet($arguments['PageButtonClass']))
			$this->page_button_class = $arguments['PageButtonClass'];
		if(IsSet($arguments['TabButtonClass']))
			$this->tab_button_class = $arguments['TabButtonClass'];
		$this->SetStyles();
		$t = count($this->pages);
		for(Reset($this->pages), $p = 0; $p < $t; Next($this->pages), ++$p)
		{
			$page = Key($this->pages);
			if(strlen($page) == 0)
				return('it was specified a page with an empty name');
			if(strlen($error = $form->AddInput(array(
				'TYPE'=>'submit',
				'ID'=>$this->button.$page,
				'NAME'=>$this->button.$page,
				'VALUE'=>(IsSet($this->pages[$page]['Name']) ? $this->pages[$page]['Name'] : $page),
				'SubForm'=>(IsSet($this->pages[$page]['SubForm']) ? $this->pages[$page]['SubForm'] : $this->button.'_sub_form'),
				'IgnoreAnonymousSubmitCheck'=>1,
				'DisableResubmitCheck'=>1,
				'ONMOUSEUP'=>'this.clicked = true',
				'ONKEYDOWN'=>'this.clicked = (event.keyCode == 13)',
				'ONCLICK'=>'if(!this.clicked) return false; this.clicked = false; '.$this->switch.'(this.form, '.$form->EncodeJavascriptString($page).'); return false;'
			))))
				return($error);
		}
		if(IsSet($arguments['FadePagesTime']))
		{
			$time_type = GetType($this->fade_pages_time = $arguments['FadePagesTime']);
			if((strcmp($time_type,'double')
			&& strcmp($time_type,'integer'))
			|| $this->fade_pages_time < 0)
				return('it was not specified a valid fade pages time');
		}
		if($this->fade_pages_time > 0
		&& strlen($error = $form->AddInput(array(
				'TYPE'=>'custom',
				'ID'=>$this->page.'animation',
				'CustomClass'=>'form_animation_class',
				'JavascriptPath'=>(IsSet($arguments['JavascriptPath']) ? $arguments['JavascriptPath'] : '')
			))))
			return($error);
		return($form->ConnectFormToInput($this->input, 'ONERROR', 'SwitchPage', array('InputsPage'=>'Invalid')));
	}

	Function AddInputPart(&$form)
	{
		if(strlen($error = $form->AddInputPart($this->page))
		|| strlen($error = $form->AddDataPart('<table width="100%" cellpadding="'.$this->tab_padding.'" cellspacing="0"><tr>')))
			return($error);
		$t = count($this->pages);
		$page_class = (strlen($this->page_class) ? $this->page_class : $this->class.'page');
		$tab_class = (strlen($this->tab_class) ? $this->tab_class : $this->class.'tab');
		$gap_class = (strlen($this->gap_class) ? $this->gap_class : $this->class.'gap');
		$page_button_class = (strlen($this->page_button_class) ? $this->page_button_class : $this->class.'page_button');
		$tab_button_class = (strlen($this->tab_button_class) ? $this->tab_button_class : $this->class.'tab_button');
		for(Reset($this->pages), $p = 0; $p < $t; Next($this->pages), ++$p)
		{
			$page = Key($this->pages);
			$button  = $this->button.$page;
			$is_page = !strcmp($page, $this->current_page);
			if(strlen($error = $form->SetInputProperty($button, 'CLASS', $is_page ? $page_button_class : $tab_button_class))
			|| strlen($error = $form->AddDataPart('<td class="'.HtmlSpecialChars($gap_class).'">&nbsp;</td><td id="'.HtmlSpecialChars($this->tab.$page).'" class="'.HtmlSpecialChars($is_page ? $page_class : $tab_class).'">'))
			|| strlen($error = $form->AddInputPart($button))
			|| strlen($error = $form->AddDataPart('</td>')))
				return($error);
		}
		if(strlen($error = $form->AddDataPart('<td class="'.HtmlSpecialChars($gap_class).'" width="99%">&nbsp;</td></tr></table>')))
			return($error);
		for(Reset($this->pages), $p = 0; $p < $t; Next($this->pages), ++$p)
		{
			$page = Key($this->pages);
			$is_page = !strcmp($page, $this->current_page);
			if(strlen($error = $form->AddDataPart('<div id="'.HtmlSpecialChars($this->page.$page).'" style="display: '.($is_page ? 'block' : 'none').'">'))
			|| strlen($error = $this->AddPagePart($form, $page))
			|| strlen($error = $form->AddDataPart('</div>')))
				return($error);
		}
		return('');
	}

	Function LoadInputValues(&$form, $submitted)
	{
		$t = count($this->pages);
		for(Reset($this->pages), $p = 0; $p < $t; Next($this->pages), ++$p)
		{
			$page = Key($this->pages);
			if($form->WasSubmitted($this->button.$page))
			{
				$form->SetInputValue($this->page, $this->current_page = $page);
				return;
			}
		}
		$page = $form->GetInputValue($this->page);
		if(IsSet($this->pages[$page]))
			$this->current_page = $page;
		else
			$form->SetInputValue($this->page, $this->current_page);
	}

	Function PageHead(&$form)
	{
		$eol = $form->end_of_line;
		$page_class = (strlen($this->page_class) ? $this->page_class : $this->class.'page');
		$tab_class = (strlen($this->tab_class) ? $this->tab_class : $this->class.'tab');
		$page_button_class = (strlen($this->page_button_class) ? $this->page_button_class : $this->class.'page_button');
		$tab_button_class = (strlen($this->tab_button_class) ? $this->tab_button_class : $this->class.'tab_button');
		Reset($this->pages);
		$context=array(
			'Name'=>'Fade tab',
			'Effects'=>array(
				array(
					'Type'=>'CancelAnimation',
					'Animation'=>'Fade tab'
				),
				array(
					'Type'=>'FadeIn',
					'DynamicElement'=>'\''.$this->page.'\' + page',
					'Duration'=>$this->fade_pages_time,
					'Visibility'=>'display'
				),
			)
		);
		if($this->fade_pages_time>0
		&& strlen($fade_error = $form->GetJavascriptConnectionAction('form', $this->input, $this->page.'animation', 'ONCHANGE', 'AddAnimation', $context, $fade_javascript)))
			$form->OutputDebug('could not setup fade animation for paged layout input '.$this->input.': '.$fade_error);
		$head = '<script type="text/javascript"><!--'.$eol.
			'function '.$this->switch.'(form, page)'.$eol.
			'{'.$eol.
			' var old_page'.$eol.
			' var e'.$eol.$eol.
			' old_page = '.$form->GetJavascriptInputValue('form', $this->page).$eol.
			' '.$form->GetJavascriptSetInputValue('form', $this->page, 'page').$eol.
			' if((e = document.getElementById(\''.$this->tab.'\' + old_page)))'.$eol.
			'  e.className = \''.$tab_class.'\''.$eol.
			' if((e = document.getElementById(\''.$this->button.'\' + old_page)))'.$eol.
			'  e.className = \''.$tab_button_class.'\''.$eol.
			' if((e = document.getElementById(\''.$this->page.'\' + old_page)))'.$eol.
			'  e.style.display = \'none\''.$eol.
			' if((e = document.getElementById(\''.$this->tab.'\' + page)))'.$eol.
			'  e.className = \''.$page_class.'\''.$eol.
			' if((e = document.getElementById(\''.$this->button.'\' + page)))'.$eol.
			'  e.className = \''.$page_button_class.'\''.$eol.
			(($this->fade_pages_time>0 && strlen($fade_error) == 0) ?
			' if(page != old_page)'.$eol.
			' {'.$eol.
			'  '.$fade_javascript.$eol.
			' }'.$eol
			 : '').
			' if((e = document.getElementById(\''.$this->page.'\' + page)))'.$eol.
			'  e.style.display = \'block\''.$eol.
			'}'.$eol.
			'// --></script>'.$eol;
		if(strlen($this->page_class)==0
		|| strlen($this->tab_class)==0
		|| strlen($this->gap_class)==0
		|| strlen($this->page_button_class)==0
		|| strlen($this->tab_button_class)==0)
		{
			$head .= '<style type="text/css"><!--'.$eol.
				(strlen($this->page_class) ? '' : '.'.$this->class.'page {'.$this->styles['page'].' }'.$eol).
				(strlen($this->tab_class) ? '' : '.'.$this->class.'tab {'.$this->styles['tab'].' }'.$eol).
				(strlen($this->gap_class) ? '' : '.'.$this->class.'gap {'.$this->styles['gap'].' }'.$eol).
				(strlen($this->page_button_class) ? '' : '.'.$this->class.'page_button {'.$this->styles['page_button'].' }'.$eol).
				(strlen($this->tab_button_class) ? '' : '.'.$this->class.'tab_button {'.$this->styles['tab_button'].' }'.$eol).
				'// --></style>'.$eol;
		}
		return($head);
	}

	Function AddPagePart(&$form, $page)
	{
		return($form->AddInputPart($page));
	}

	Function GetContainedPageInputs(&$form, $page, &$contained)
	{
		if(IsSet($this->contained[$page]))
		{
			$contained = $this->contained[$page];
			return('');
		}
		if(strlen($error = $form->GetContainedInputs($page, '', $contained)))
			return($error);
		$this->contained[$page] = $contained ;
		return('');
	}


	Function ValidateInput(&$form)
	{
		if(count($form->Invalid) == 0)
			return('');
		Reset($form->Invalid);
		$invalid = Key($form->Invalid);
		$flip = function_exists('array_flip');
		$tp = count($this->pages);
		for($found = 0, Reset($this->pages), $p = 0; $p < $tp; ++$p, Next($this->pages))
		{
			$page = Key($this->pages);
			if(strlen($error = $this->GetContainedPageInputs($form, $page, $page_contained)))
				return($error);
			if($flip)
			{
				$contained = array_flip($page_contained);
				if(($found = IsSet($contained[$invalid])))
					break;
			}
			else
			{
				$tc = count($page_contained);
				for($c = 0; $c < $tc; ++$c)
				{
					if(($found = !strcmp($invalid, $page_contained[$c])))
						break 2;
				}
					
			}
		}
		return($found ? $form->SetInputValue($this->page, $this->current_page = $page) : '');
	}

	Function GetContainedInputs(&$form, $kind, &$contained)
	{
		$contained = array($this->input);
		$tp = count($this->pages);
		for(Reset($this->pages), $p = 0; $p < $tp; ++$p, Next($this->pages))
		{
			$page = Key($this->pages);
			if(strlen($kind) == 0)
			{
				if(strlen($error = $this->GetContainedPageInputs($form, $page, $page_contained)))
					return($error);
			}
			elseif(strlen($error = $form->GetContainedInputs($page, $kind, $page_contained)))
				return($error);
			$tc = count($page_contained);
			for($c = 0; $c < $tc; ++$c)
				$contained[] = $page_contained[$c];
		}
		return('');
	}

	Function GetJavascriptConnectionAction(&$form, $form_object, $from, $event, $action, &$context, &$javascript)
	{
		switch($action)
		{
			case 'SwitchPage':
				$javascript = '';
				if(IsSet($context['Page']))
				{
					if(!IsSet($this->pages[$context['Page']]))
						return($context['Page'].' is not a valid page to switch');
					$page = $form->EncodeJavascriptString($context['Page']);
					$conditional = 0;
				}
				elseif(IsSet($context['InputsPage']))
				{
					$inputs = $context['InputsPage'];
					$javascript.='var pages = {';
					$tp = count($this->pages);
					for(Reset($this->pages), $p = 0; $p < $tp; ++$p, Next($this->pages))
					{
						$page = Key($this->pages);
						if(strlen($error = $this->GetContainedPageInputs($form, $page, $contained)))
							return($error);
						$page_value = $form->EncodeJavascriptString($page);
						$tc = count($contained);
						for($c = 0; $c < $tc; ++$c)
						{
							if($c > 0
							|| $p > 0)
								$javascript.=', ';
							$javascript.=$form->EncodeJavascriptString($contained[$c]).': '.$page_value;
						}
					}
					$javascript.=' }; var page = \'\'; for(var i in '.$inputs.') { if(pages[i]) { page = pages[i]; break; } }; ';
					$page = 'page';
					$conditional = 1;
				}
				else
					return('it was not specified a valid page to switch');
				$javascript .= ($conditional ? 'if('.$page.'.length) ' : '').$this->switch.'(form, '.$page.');';
				break;
			default:
				return($this->DefaultGetJavascriptConnectionAction($form, $form_object, $from, $event, $action, $context, $javascript));
		}
		return('');
	}
};

?>