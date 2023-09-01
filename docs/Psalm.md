# Psalm integration

Obviously, for convenient work with this library, you need [Psalm](https://github.com/vimeo/psalm).

This library ships with Psalm plugin.
Currently, `pipe` combinator signature cannot be expressed in Psalm.
Psalm plugin leverages this issue.

To enable:

```shell
./vendor/bin/psalm-plugin enable Fp4\\PHP\\PsalmIntegration\\Plugin
```

Also, this plugin:
1) Infers more concrete type for `filter`/`partition` functions.
2) Infers `bindable` a.k.a. do-notation a.k.a. for-comprehension.
3) Infers other functions that impossibly express in Psalm

The full plugin description will be soon.
