<!-- cSpell:ignore tocstop lando lndo intelephense appserver MAMP XAMPP Laragon autocrlf safecrlf -->
# Contributor's Guide

A reference for development team members.

## Table of Contents

- [Contributor's Guide](#contributors-guide)
    - [Table of Contents](#table-of-contents)
    - [Setup for development](#setup-for-development)
        - [General setup](#general-setup)
        - [Git Configuration](#git-configuration)
        - [Visual Studio Code configuration](#visual-studio-code-configuration)
        - [MemberDash Debugging](#memberdash-debugging)
        - [Xdebug](#xdebug)
    - [Coding Standards](#coding-standards)
        - [Coding quality commands](#coding-quality-commands)
    - [Branching and Committing](#branching-and-committing)
        - [Branch naming explanation](#branch-naming-explanation)
        - [Workflow suggestions](#workflow-suggestions)
    - [Testing Payment Integration](#testing-payment-integration)
        - [Paypal Test](#paypal-test)
        - [Stripe Test](#stripe-test)
    - [Helpful SQL's](#helpful-sqls)

## Setup for development

To start developing for `MemberDash` you need at least:

- [Git](https://git-scm.com/) (Please read some `git` considerations [here](#git-configuration))
- A code editor like [Visual Studio Code](https://code.visualstudio.com), [PhpStorm](https://www.jetbrains.com/phpstorm/), [Sublime Text](https://www.sublimetext.com/), etc.
- A PHP development environment like [LocalWP](https://localwp.com/), [VVV](https://varyingvagrantvagrants.org/), [MAMP](https://www.mamp.info/), [XAMPP](https://www.apachefriends.org/index.html) or [Laragon](https://varyingvagrantvagrants.org/) (Windows only)
- PHP [`composer`](https://getcomposer.org)
- `node` and `npm`. We recommend using [NVM](https://github.com/nvm-sh/nvm) to install node, this will ensure that you have the correct node version

### General setup

To configure the environment, you need these steps:

- Install and configure WordPress
- Clone the [MemberDash](https://github.com/stellarwp/memberdash) in your `plugins/` directory as `memberdash`
- Enable the correct `node` and `npm` version
- Install the required `npm` packages
- Install the required dependencies
- Compile scripts for release
  
In a nutshell, this is the process:

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/stellarwp/memberdash memberdash
cd memberdash
nvm install
npm ci && npm run install-deps
npm run release
```

After this, you can open your editor an start developing

### Git Configuration

If you are running Windows, please make sure you are not using CRLF line endings on the submitted code. This can be achieved by creating a `.gitconfig` on your home directory (usually found under `Users\{username}\.gitconfig`) with at least the following contents:

```gitconfig
[core]
autocrlf = false
safecrlf = false
eol = lf
```

Optionally, but very recommended is that you set up an SSH key for GitHub. For more information, please review <https://docs.github.com/en/authentication/connecting-to-github-with-ssh>

### Visual Studio Code configuration

If you use `Visual Studio Code` you'll get a "recommendations" pop-up the first time you open the project with it. As the name implies, they are just recommendations and you can install the ones that better suit your needs. You can review which are the recommended plugins by searching for `@recommended` on the extension panel.

This the minimum recommended extensions:

- Code Spell Checker <https://marketplace.visualstudio.com/items?itemName=streetsidesoftware.code-spell-checker>
- EditorConfig for VS Code <https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig>
- ESLint <https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint>
- Markdown All in One <https://marketplace.visualstudio.com/items?itemName=yzhang.markdown-all-in-one>
- markdownlint <https://marketplace.visualstudio.com/items?itemName=DavidAnson.vscode-markdownlint>
- PHP Sniffer (and code fixer) <https://marketplace.visualstudio.com/items?itemName=wongjn.php-sniffer>
- PHPStan <https://marketplace.visualstudio.com/items?itemName=swordev.phpstan>
- Stylelint <https://marketplace.visualstudio.com/items?itemName=stylelint.vscode-stylelint>

And this are nice to haves:

- GitHub Copilot (optional) <https://marketplace.visualstudio.com/items?itemName=GitHub.copilot>
- GitLens <https://marketplace.visualstudio.com/items?itemName=eamodio.gitlens>
- Minify <https://marketplace.visualstudio.com/items?itemName=HookyQR.minify>
- PHP Intelephense <https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client>
- PHP Tools <https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode>
- Window Colors <https://marketplace.visualstudio.com/items?itemName=stuart.unique-window-colors>
- WP Debug Log Panel <https://marketplace.visualstudio.com/items?itemName=Profet.wp-debug-log-panel>

### MemberDash Debugging

When you are developing, you need to visualize any possible error and warning you have in your code. That's why is highly recommend that you enable the following debugging flags in your `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', true);
define( 'WP_DEBUG_LOG', true );
define( 'MEMBERDASH_DEBUG', true );
define( 'STELLARWP_TELEMETRY_SERVER', 'https://telemetry-dev.stellarwp.com/api/v1' );
```

### Xdebug

[Xdebug](https://xdebug.org/docs/install)

- Please refer to the installation instructions for your system. Delicious Brains has a few tutorials on setup and configuration.

## Coding Standards

We are continually optimizing the code to follow [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/)

The repo contains configuration files (like `.editorconfig` and `phpcs.xml`) following WP Core coding standards. Please make sure your editor supports EditorConfig, PHPCS and ESlint (natively or through extensions).

> If you are using Visual Studio Code, you'll get a popup with a list of [recommended extensions](.vscode/extensions.json)

### Coding quality commands

In order to help you with the coding quality check locally, we have the commands below:

| command                                            | description                                                                                                                  |
| -------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `composer check <file.php>`                        | Runs the phpcs check in changed lines (based on the main branch), PHPStan check and cspell check in a file.                  |
| `composer check <directory/**/*.php>`              | Runs the phpcs check in changed lines (based on the main branch), PHPStan check and cspell check in a directory recursively. |
| `composer check-full`                              | Runs the phpcs check in changed lines (based on the main branch), PHPStan check and cspell check in the whole project.       |
| `composer phpcs-partial <branch> <file.php>`       | Runs the phpcs check in changed lines, based on the branch, in a file.                                                       |
| `composer phpcs-ci [<branch>]`                     | Runs the phpcs check in changed lines, based on the branch or in the main branch by default, in the whole project.           |
| `composer phpcs-autofix [<file.php or directory>]` | Fix fixable phpcs errors automatically.                                                                                      |

More commands can be found in the `composer.json` file.

## Branching and Committing

We're following a specific branch naming conventions.

### Branch naming explanation

- **main**: The main branch of the project. That branch contains the latest stable version of the plugin (which was used to create the latest release).

- **release/Myy.anything** (e.g. `release/M23.something`): That's a main development branch. All development tasks should be merged to a main development branch. Then, at the end of a release cycle, the development branch will be merged to the main branch. We can have more than one development branch at the same time.

- **MEMDASH-XXXX-\<something\>**: That's a working branch where _XXXX_ is the number of the related JIRA ticket. When the task is done, we should submit a pull request to merge it to the related main development branch. **-\<something\>** is optional and can be used to describe the task.

### Workflow suggestions

1. Once you get a JIRA ticket to work on, you can create a branch to the ticket with the commands below (you can also create the branch using the GitHub interface):

 ```bash
 # checkout the main branch
 git checkout main

 # ensure you have the latest version of the code
 git pull

 # create a new branch for the ticket from the main branch and switch to it
 git checkout -b MEMDASH-XXXX-something
 ```

 Some notes:

- When you have created the working branch in GitHub, the ticket status should be updated to "In Progress", and the ticket should be assigned to you.

- If the Jira ticket has a long task, you can split it into smaller tasks, creating branches for each of them that should be merged into the ticket branch through the pull request from your small task branch to the ticket branch. That's important to not create a huge pull request, very difficult to review and merge.
  
- Use a meaningful name for the pull request title (e.g., `Fix - Conflict between MemberDash and LearnDash`). You don't need to include the ticket number in the title. Instead, include the ticket number in the description of the pull request. Example: `:ticket: [MEMDASH-XXXX]`. This will automatically add a link to the ticket in the pull request description. If you are working on a Jira subtask ticket, you can add the parent ticket number to the pull request description, e.g., `:ticket: [MEMDASH-parent MEMDASH-subtask]`.
  
- In order to Jira track and associate the commits properly, **the commit message has to start with the ticket number**, e.g., `MEMDASH-XXXX: My commit message`.

- If you are working on a Jira subtask ticket, you should add the parent ticket number to the commit message, e.g., `MEMDASH-parent MEMDASH-subtask: My commit message`.

- Don't use hard-release numbers in `@since`/`@deprecated` tags. Use `@since TBD`/`@deprecated TBD` instead. Then, in the release preparation, we can replace all `@since TBD`/`@deprecated TBD` tags with the actual release number.

- Update `changelog.txt` file to reflect your changes in the upcoming release e.g. `* Fix - Conflict between MemberDash and LearnDash.`. These are the categories and order of the lines as they typically appear:
    - Feature (self explanatory)
    - Security (security fix)
    - Fix (general fix)
    - Tweak (general change)
    - Performance (performance enhancement)
    - Deprecate (deprecation activity)
    - Language (pot file changes - that is output from GlotPress)

1. You can split the task into smaller tasks creating branches for each of them, and submitting pull requests to the ticket branch. So, other team members can review your code properly, suggest changes and learn about the part of the code that is building. Some tips:
   - Each small task should work separately, implementing a small piece of the logic without breaking anything.

   - To indicate that the PR is not ready, you can set up the PR as a `Draft` or include the `WIP` term in the pull request title.

   - When your pull request is ready to review, you can ask for a review by choosing team members in `Reviewers`.

   - Don't forget to update the Jira ticket with relevant information about the changes you are working on.

2. Once the task is ready to review, the developer should create a pull request to merge changes into the release branch (e.g. `release/L23.something`) with the notes below:
   - Add a team member as a reviewer to do the code review.

   - If it is the last round of code for the ticket (the functionality of the ticket is fully implemented), add the `Branch Review` label to the pull request. It means the code is ready to be reviewed by the QA team.

   - Update your Jira ticket with the testing instructions for QA to save their time. Ideally there should be a step-by-step guide or screenshots/short video.

   - You can skip the QA process if it's not necessary. In this case, add the `No QA Needed` label to the pull request.

3. A QA team member will check the ticket:
   - If tests passed, the tester will add the label `QA Approved` to the pull request.

   - If not passed, the tester should **remove** the label `Branch Review`. Please, don't forget to add a comment to the ticket with the reason for the failure.

4. An automation will monitor the pull request.
   - If the pull request:
     - Has not the label `On Hold` **And**
     - Has the label `QA Approved` or the label `No QA Needed` **And**
     - Is approved by a team member **And**
     - All quality control checks are passing
   - Then:
     - The pull request label will be updated to `Ready to Merge`

5. After it, the **developer who created the pull request** can merge it.

6. Finally, in sometime, the QA team member will review it again in the release branch and, if everything is fine, the ticket is set to `Done`. Otherwise, the ticket is set to `In Progress` and the developer should fix the issue, recreating the feature branch and submitting a new pull request.

7. At any time, we can use the `On Hold` label in the pull request to put the merge on hold. It's useful when we need to wait for a certain condition to be met.

## Testing Payment Integration

You can test Paypal, Stripe and other gateways in localhost using the following steps.

If you want a public URL with SSL (although it is not required for tests below), you can use [ngrok](https://ngrok.com/) for it.

### Paypal Test

In the <https://developer.paypal.com>, create two sandbox accounts (Business and Personal). Use the Business account to login to the <https://www.sandbox.paypal.com> and enable the "Instant Payment Notification" option in <https://www.sandbox.paypal.com/merchantnotification/ipn/preference>. You can use a random URL (e.g. <https://test.com>) for the IPN Notification URL as it will be overwritten in the LD button.

On the plugin payment settings, fill up the PayPal Email setting with the email address of the Business account (don't forget to select **Sandbox mode**) and use the Personal account to simulate a purchase.

Use [localtunnel](https://github.com/localtunnel/localtunnel) to test PayPal IPN.

```sh
# install localtunnel
npm install -g localtunnel

# start tunnel to port 80
# put the generated url in the PayPal settings configurations in the "IPN listening URL" option
# example: https://swift-eagle-45.loca.lt/ms-payment-return/paypalstandard
lt -p 80 --print-requests true
```

You can check the notifications history using your Business account in <https://www.sandbox.paypal.com/merchantnotification/ipn/history>.

### Stripe Test

You can connect your account and check the **Test Mode** option on the plugin settings.

Use [stripe-cli](https://stripe.com/docs/stripe-cli) to test the Stripe webhook.

Cards to test can be found [here](https://stripe.com/docs/testing#cards).

```sh
# login into stripe account
stripe login

# listen and forward events to MemberDash
stripe listen --forward-to http://localhost/?memberdash-integration=stripe
```

## Helpful SQL's

Here are some helpful SQL's for testing purposes.

```sql
# delete post_type data to reset tests
DELETE FROM `wp_postmeta` where post_id in (select id from wp_posts where post_type like 'ms_%' and post_type != 'ms_membership');
DELETE FROM `wp_posts` where post_type like '%ms_%' and post_type != 'ms_membership';

# delete user data
DELETE FROM `wp_usermeta` WHERE meta_key like '%ms_%';

# delete all Telemetry settings
DELETE FROM `wp_options` WHERE option_name LIKE '%telemetry%';
```
