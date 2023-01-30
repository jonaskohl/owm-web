$(window).on("load", function() {
    function resize() {
        $("#main").css("height", "auto");
        void($("#main").height());
        var minHeight = ($(document).height() - 212);
        var maxHeight = $("#main").prop("scrollHeight");
        $("#main").css("height", Math.max(minHeight, maxHeight) + "px");
    }

    $(window).on("resize", function() {
        resize();
    });

    resize();
});
