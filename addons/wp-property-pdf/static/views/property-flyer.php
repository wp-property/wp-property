<?php
/**
 * PDF Flyer default template
 */ 
?>
<html>
    <head>
        <title></title>
        <style type="text/css">
            div.heading_text {
              font-size: <?php echo $wpp_pdf_flyer['font_size_header']; ?>px;
              border-bottom: 2px solid <?php echo (!empty($wpp_pdf_flyer['section_bgcolor']) ? $wpp_pdf_flyer['section_bgcolor'] : '#DADADA'); ?>;
            }

            .pdf-text {
              font-size: <?php echo $wpp_pdf_flyer['font_size_content']; ?>px;
            }

            .pdf-text .attribute .separator {
              display: inline-block;
              padding: 0 5px 0 2px;
            }

            .pdf-note {
              font-size: <?php echo $wpp_pdf_flyer['font_size_note']; ?>px;
            }

            table.bg-header {
              background-color: <?php echo (!empty($wpp_pdf_flyer['header_color']) ? $wpp_pdf_flyer['header_color'] : '#EDEDED'); ?>;
            }

            table.bg-section {
              background-color: <?php echo (!empty($wpp_pdf_flyer['section_bgcolor']) ? $wpp_pdf_flyer['section_bgcolor'] : '#EDEDED'); ?>;
            }
        </style>
    </head>
    <body><table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td height="15">&nbsp;
                </td>
            </tr>
      <?php if ( !empty( $wpp_pdf_flyer[ 'logo_url' ] ) ) : ?>
        <tr>
                <td><img class='header_logo_image' src="<?php echo $wpp_pdf_flyer[ 'logo_url' ]; ?>" alt=""/>
                </td>
            </tr>
        <tr>
                <td height="15">&nbsp;
                </td>
            </tr>
      <?php endif; ?>
      <?php if ( !empty( $wpp_pdf_flyer[ 'pr_title' ] ) ) : ?>
        <tr>
                <td><table cellspacing="0" cellpadding="10" border="0" class="bg-header" style="text-align:left;" width="100%">
                        <tr>
                            <td><span style="font-size:<?php echo $wpp_pdf_flyer[ 'font_size_header' ]; ?>px;"><b><?php echo $property[ 'post_title' ]; ?></b></span>
                              <?php $tagline = isset( $property[ 'tagline' ] ) ? $property[ 'tagline' ] : ''; ?>
                              <?php if ( !empty( $wpp_pdf_flyer[ 'pr_tagline' ] ) && !empty( $tagline ) ) : ?>
                                <br/>
                                <span style="font-size:<?php echo $wpp_pdf_flyer[ 'font_size_content' ]; ?>px;color:#797979;"><?php echo $tagline ?></span>
                              <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
      <?php endif; ?>
      <tr>
                <td height="15">&nbsp;
                </td>
            </tr>
            <tr>
                <td><table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td width="<?php echo $wpp_pdf_flyer[ 'first_col_width' ] ?>"><table>
                                <?php if ( !empty( $wpp_pdf_flyer[ 'featured_image_url' ] ) ) : ?>
                                  <tr>
                                    <td colspan="3"><table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                                        <tr>
                                            <td><img src="<?php echo $wpp_pdf_flyer[ 'featured_image_url' ]; ?>" width="<?php echo( $wpp_pdf_flyer[ 'first_col_width' ] - 20 ); ?>" alt=""/>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                  <tr>
                                    <td height="15">&nbsp;
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td id="left_column" border="0" width="<?php echo( $wpp_pdf_flyer[ 'first_col_width' ] / 2 - 7 ); ?>"><table cellspacing="0" cellpadding="0" border="0">
                                        <?php do_action( 'wpp_flyer_left_column', $property, $wpp_pdf_flyer ); ?>
                                        <tr>
                                            <td></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td width="14">&nbsp;
                                    </td>
                                    <td id="middle_column" border="0" width="<?php echo( $wpp_pdf_flyer[ 'first_col_width' ] / 2 - 7 ); ?>"><table cellspacing="0" cellpadding="0" border="0">
                                        <?php do_action( 'wpp_flyer_middle_column', $property, $wpp_pdf_flyer ); ?>
                                        <tr>
                                            <td></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td width="15">&nbsp;
                            </td>
                            <td width=""><table cellspacing="0" cellpadding="0" width="100%">
                                <?php foreach ( $wpp_pdf_flyer[ 'images' ] as $image ) : ?>
                                  <tr>
                                    <td><table cellspacing="0" cellpadding="10" border="0" class="bg-section">
                                      <tr>
                                        <td><img width="<?php echo( $image[ 'width' ] - 20 ); ?>" src="<?php echo $image[ 'link' ]; ?>" alt=""/>
                                        </td>
                                      </tr>
                                    </table></td>
                                  </tr>
                                  <tr>
                                    <td height="15">&nbsp;
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                                <?php do_action( 'wpp_flyer_right_column', $property, $wpp_pdf_flyer ); ?>
                                <tr>
                                  <td width="15">&nbsp;
                                  </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>