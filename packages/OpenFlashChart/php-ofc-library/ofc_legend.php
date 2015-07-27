<?php


class legend
{
	function legend(){}
	
	function set_position( $position )
	{
		$this->position = $position;
	}	

	function set_visible( $visible )
	{
		$this->visible = $visible;
	}

	function set_shadow( $shadow )
	{
		$this->shadow = $shadow;
	}
	
	function set_padding( $padding )
	{
		$this->padding = $padding;
	}
	
	function set_border( $border )
	{
		$this->border = $border;
	}
	
	function set_stroke( $stroke )
	{
		$this->stroke = $stroke;
	}
	
	function set_margin( $margin )
	{
		$this->margin = $margin;
	}
	
	function set_alpha( $alpha )
	{
		$this->alpha = $alpha;
	}	
	
	function set_border_colour( $border_colour )
	{
		$tmp = "border_colour";
		$this->$tmp = $border_colour;
	}
	
	function set_bg_colour( $bg_colour )
	{
		$tmp = "bg_colour";
		$this->$tmp = $bg_colour;
	}

}

