// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope this it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settongs.
 *
 * @module      format_ludilearn/settings
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/str', 'core/modal_factory', 'core/modal_events'],
    ($, Ajax, Templates, Str, ModalFactory, ModalEvents) => {
        let COURSE_ID = 0;
        let SECTION_ID = 0;
        let INVENTORY = [];
        let URL_IMG = '';

        // Set item equiped.
        let set_item_equiped = (slot, theme) => {
            Ajax.call([{
                methodname: 'format_ludilearn_set_item_equiped',
                args: {
                    courseid: COURSE_ID,
                    slot: slot,
                    theme: theme
                }
            }], true, true)[0].done((response) => {
                if (response.success) {
                    refreshInventory(slot, theme);
                    let slotElement =  $('.avatar-slot.slot-' + slot);
                    if (theme !== 0) {
                        slotElement.attr('src', URL_IMG + 'image-0' + slot + '-' + theme + '.svg');
                        slotElement.addClass('item-equiped');
                    } else {
                        slotElement.attr('src', URL_IMG + 'item_icon_default.svg');
                        slotElement.removeClass('item-equiped');
                    }
                }
            }).fail((ex) => {
                console.error(ex);
            });
        };

        // Refresh inventory.
        let refreshInventory = (slot, theme) => {
            INVENTORY.forEach((slots, index) => {
                if (slots.theme === slot) {
                    INVENTORY[index].items.forEach((item, index) => {
                        if (item.theme === theme) {
                            INVENTORY[index].items[index].equiped = true;
                        } else {
                            INVENTORY[index].items[index].equiped = false;
                        }
                    });
                }
            });
        };

        // Get inventory.
        let get_inventory = () => {
            Ajax.call([{
                methodname: 'format_ludilearn_get_inventory',
                args: {
                    courseid: COURSE_ID,
                    sectionid: SECTION_ID
                }
            }], true, true)[0].done((response) => {
                INVENTORY = response.inventory;
                openBag();
            }).fail((ex) => {
                console.error(ex);
            });
        };

        // Event when open bag.
        let openBag = () => {
            $('.avatar-bag-close').on('click', (event) => {
                $('.avatar-bag-close').hide();
                $('.avatar-bag-open').attr('style', 'display: block;');

                createModal();
            });
        };

        // Creation of the Modal displaying inventory.
        let createModal = async() => {
            const bodyContent = await Templates.render('format_ludilearn/avatar/items', {inventory: INVENTORY});
            const modal = await ModalFactory.create({
                title: Str.get_string('inventory', 'format_ludilearn'),
                body: bodyContent,
                footer: '',
            });
            modal.show();
            const $root = await modal.getRoot();
            const root = $root[0];
            $root.on(ModalEvents.shown, () => {
                openTab(1);
                selectItem();
            });
            $root.on(ModalEvents.hidden, () => {
                // Destroy modal
                modal.destroy();
                $('.avatar-bag-close').show();
                $('.avatar-bag-open').hide();
            });
        };

        let selectItem = () => {
            $('.avatar-item-owned').on('click', (event) => {
                let slot = $(event.currentTarget).data('slot');
                let theme = $(event.currentTarget).data('theme');
                let equipButton = $('.equip-button');
                $('.avatar-item-owned').removeClass("selected");
                equipButton.hide();

                $(event.currentTarget).addClass("selected");
                if (!$(event.currentTarget).hasClass("avatar-item-equiped")) {
                    $(event.currentTarget).find('.equip-button').show();
                }

                equipButton.on('click').on('click', function() {
                    set_item_equiped(slot, theme);
                    $('.avatar-item-equiped').removeClass('avatar-item-equiped');
                    $(event.currentTarget).addClass('avatar-item-equiped');
                    equipButton.hide();
                });
            });
        };

        let openTab = (slot) => {
            let tabInventory = $('.tab-inventory');
            if (slot !== null) {
                $('.tab-content-inventory').hide();
                $('#tab-content-slot-' + slot).show();
                tabInventory.removeClass('active').attr('aria-selected', 'false');
                $('#tab-slot-' + slot).addClass('active').attr('aria-selected', 'true');
            }
            tabInventory.on('click keydown', function(event) {
                if (event.type === 'click' || (event.type === 'keydown' && (event.key === 'Enter' || event.key === ' '))) {
                    const slotEvent = $(this).data('tab');
                    $('.tab-content-inventory').hide();
                    $('#tab-content-slot-' + slotEvent).show();
                    tabInventory.removeClass('active').attr('aria-selected', 'false');
                    $('#tab-slot-' + slotEvent).addClass('active').attr('aria-selected', 'true');
                }
            });
        };

        return {
            init: (courseid, sectionid, urlimages) => {
                COURSE_ID = courseid;
                SECTION_ID = sectionid;
                URL_IMG = urlimages;
                get_inventory();
            }
        };
    });
