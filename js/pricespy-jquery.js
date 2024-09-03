jQuery( document ).ready(function($) {
    $(".prspy-main-prices").click(function(){
        if($(this).hasClass("prspy-open")){
            $(this).find(".prspy-arrow").removeClass("up").addClass("down");
            $(this).removeClass("prspy-open");
            $(this).nextUntil("tr.prspy-main-prices").hide();
        }else{
            $(this).find(".prspy-arrow").removeClass("down").addClass("up");
            $(this).addClass("prspy-open");
            $(this).nextUntil("tr.prspy-main-prices").show();
        }
//        $(".prspy-main-prices").removeClass("prspy-open");
//        $("tr.prspy-atv-prices, tr.prspy-details").hide();
//      $(this).siblings("tr.prspy-atv-prices").show();
//        $(this).nextUntil("tr.prspy-main-prices").show();
    });
});