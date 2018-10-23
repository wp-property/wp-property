<?php

namespace UsabilityDynamics\UI {

  if ( ! class_exists( 'UsabilityDynamics\UI\Field_Readonly' ) ) {

    class Field_Readonly extends Field_Text {

      /**
       * Get field HTML
       *
       * @param mixed  $value
       * @param array  $field
       *
       * @return string
       */
      static public function html( $value, $field ) {
        $value = apply_filters( 'ud::ui::field::readonly::value', $value, $field );

        if( $field->disabled ) {
          return sprintf(
            '<input type="text" readonly="readonly" class="sui-text" id="%s" value="%s" size="%s" %s>%s',
            $field->id,
            $value,
            $field->size,
            !$field->datalist ?  '' : "list='{$field->datalist[ 'id' ]}'",
            self::datalist_html( $field )
          );
        } else {
          return sprintf(
            '<input type="text" readonly="readonly" class="sui-text" name="%s" id="%s" value="%s" data-extra="%s" size="%s" %s>%s',
            $field->field_name,
            $field->id,
            $value,
            $field->extra,
            $field->size,
            !$field->datalist ?  '' : "list='{$field->datalist[ 'id' ]}'",
            self::datalist_html( $field )
          );
        }


      }

    }

  }

}
