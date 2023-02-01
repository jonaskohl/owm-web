$(window).on("load", function() {
    function resize() {
        $("#main").css("height", "");
        void($("#main").height());
        var minHeight = ($(document).height() - $("#header").outerHeight() - $("#footer").outerHeight() - $("#nav").outerHeight() - parseInt($("#nav").css("margin-top")) - 4);
        var maxHeight = $("#main").prop("scrollHeight");
        $("#main").css("height", Math.max(minHeight, maxHeight) + "px");
    }

    $(window).on("resize", function() {
        resize();
    });

    resize();

    window.$owm = window.$owm || {};
    window.$owm.resizePageHeight = resize;
});
