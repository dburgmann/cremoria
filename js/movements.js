$(document).ready(function() {

    /**
     * Prevents changes on choosen Units
     * and displays the movement settings
     */
    $('#selectArmy').click(function(){
        var ap = 0;
        var hp = 0;
        var cp = 0;
        var sp = 10000;
        var temp = 0;
        var check = false;

        //check if at least one unit is selected
        $('input[name^="units"]').each(function(){
            if($(this).val() > 0){
                check = true;
            }
        });
        if(!check) return;


        $('input[name^="units"]').each(function(){
            //Disable all inputs
            $(this).attr('readonly', true);
            
            //Calculate attribute sums of choosen units
            //Table: Name | Ap | Hp | Cp | Sp | no | input
            var quantity = $(this).val();
            if(quantity > 0){
               ap = ap + $(this).parent().prev().prev().prev().prev().prev().text() * quantity;
               hp = hp + $(this).parent().prev().prev().prev().prev().text() * quantity;
               cp = cp + $(this).parent().prev().prev().prev().text() * quantity;

               temp = parseInt($(this).parent().prev().prev().text());
               //Determine lowest sp (= army sp)
               if(temp < sp){
                   sp = temp;
               }
            }

            //Write sum values into table cells and make visible
            $('#totalAp').text(ap);
            $('#totalHp').text(hp);
            $('#totalCp').text(cp);
            $('#totalSp').text(sp);
            $('#maxCap').text(cp);
            $('#totalRow').removeClass('hidden');

        })

        //Hide Army Select Button
        $('#selectArmy').hide();

        //Display movementsettings
        $('#movSettings').removeClass('hidden');
    })



    /**
     * Updates the remaining Cargo capacity
     */
    $('.cargoInput').change(function(){
        var usedCargo = 0;
        $('.cargoInput').each(function(){
            if(parseInt($(this).val()) > 0)
                usedCargo = usedCargo + parseInt($(this).val());
        })
        $('#usedCap').text(usedCargo);

        if(usedCargo > parseInt($('#maxCap').text())){
            $('#cargoDisplay').addClass('red');
        }
    })


    /**
     * Updates the estimatet movement duration
     */
    $('#destX').change(function(){
        var time = calcTime();
        $('#movTime').text(time);
    })

    $('#destY').change(function(){
        var time = calcTime();
        $('#movTime').text(time);
    })

    function calcTime(){
        var time    = 0;
        var distX   = 0;
        var distY   = 0;
        var distance= 0;
        var speed   = parseInt($('#totalSp').text())

        distX = parseInt($('#destX').val()) - parseInt($('#startX').val());
        distY = parseInt($('#destY').val()) - parseInt($('#startY').val());
        distance = Math.sqrt(distX * distX + distY * distY);
        //(1 + $dist)*100 * 1 / $sp
        time = parseInt((1 + distance) * 100 / speed);
        return time;
    }

})

