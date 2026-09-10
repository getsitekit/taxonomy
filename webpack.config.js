module.exports = [

    {
        entry: {
            "taxonomy": "./app/taxonomy.js",
            "post-taxonomy": "./app/post-taxonomy.js",
            "taxonomy-admin": "./app/taxonomy-admin.js"
        },
        output: {
            filename: "./app/bundle/[name].js",
        },
        module: {
            loaders: [
                {test: /\.vue$/, loader: "vue" },
                {test: /\.html$/, loader: "vue-html"},
            ]
        }
    },

];
