using ozon.Models;
using System;
using System.Collections.Generic;
using System.Data.Entity;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;
using BCrypt.Net;

namespace ozon.Views
{
    public partial class RegistrationWindow : Window
    {
        OzonContext _context;

        public RegistrationWindow()
        {
            InitializeComponent();
            _context = new OzonContext();
            _context.Users.Load();

            // Изначально кнопка регистрации неактивна
            RegisterButton.IsEnabled = false;
        }

        private bool IsValidUsername(string username)
        {
            return !string.IsNullOrEmpty(username) &&
                   username.Length >= 3 &&
                   Regex.IsMatch(username, @"^[a-zA-Z0-9_]+$");
        }

        private void UpdateRegisterButton()
        {
            bool isUsernameValid = IsValidUsername(UsernameInput.Text);
            bool isPasswordValid = !string.IsNullOrEmpty(PasswordInput.Password);
            RegisterButton.IsEnabled = isUsernameValid && isPasswordValid;
        }

        private void UsernameInput_TextChanged(object sender, TextChangedEventArgs e)
        {
            UpdateRegisterButton();

            // Валидация имени пользователя
            if (!string.IsNullOrEmpty(UsernameInput.Text) && !Regex.IsMatch(UsernameInput.Text, @"^[a-zA-Z0-9_]+$"))
            {
                UsernameInput.BorderBrush = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#CD5C5C"));
            }
            else
            {
                UsernameInput.BorderBrush = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#E0E0E0"));
            }
        }

        private void PasswordInput_PasswordChanged(object sender, RoutedEventArgs e)
        {
            UpdateRegisterButton();
        }

        private async void Register_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                if (!string.IsNullOrEmpty(UsernameInput.Text) && !string.IsNullOrEmpty(PasswordInput.Password))
                {
                    if (!IsValidUsername(UsernameInput.Text))
                    {
                        ShowStyledMessage("Имя пользователя должно содержать только латинские буквы, цифры и символ подчеркивания.", "#CD5C5C");
                        return;
                    }

                    var user = await _context.Users.FirstOrDefaultAsync(u => u.Username == UsernameInput.Text);

                    if (user != null)
                    {
                        ShowStyledMessage("Пользователь с таким именем уже существует.", "#CD5C5C");
                        return;
                    }

                    var newUser = new User
                    {
                        Username = UsernameInput.Text,
                        PasswordHash = BCrypt.Net.BCrypt.HashPassword(PasswordInput.Password),
                        UserRole = "worker"
                    };

                    _context.Users.Add(newUser);
                    await _context.SaveChangesAsync();

                    // Добавляем запись в UserActions о регистрации
                    await LogUserRegistrationAsync(newUser.Id);

                    // Добавляем уведомление о регистрации
                    int notificationId = await AddRegistrationNotificationAsync(newUser.Id);

                    // Получаем текст уведомления из NotificationType где Id = 2
                    var notificationType = await _context.NotificationTypes.FirstOrDefaultAsync(nt => nt.Id == 2);
                    string notificationMessage = notificationType?.TypeName ?? "Вы успешно зарегистрированы!";

                    // Показываем улучшенное сообщение с кнопкой OK
                    await ShowNotificationMessage(notificationMessage, "#2E8B57", notificationId);

                    LoginWindow loginWindow = new LoginWindow();
                    loginWindow.Show();
                    Close();
                }
                else
                {
                    ShowStyledMessage("Заполните все поля.", "#CD5C5C");
                }
            }
            catch (Exception ex)
            {
                ShowStyledMessage($"Ошибка регистрации: {ex.Message}", "#CD5C5C");
            }
        }

        // Метод для логирования регистрации пользователя
        private async Task LogUserRegistrationAsync(int userId)
        {
            try
            {
                var userAction = new UserAction
                {
                    ActionTypeId = "2",
                    ActionDate = DateTime.Now,
                    UserId = userId
                };

                _context.UserActions.Add(userAction);
                await _context.SaveChangesAsync();
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Ошибка при логировании регистрации: {ex.Message}");
            }
        }

        // Метод для добавления уведомления о регистрации
        private async Task<int> AddRegistrationNotificationAsync(int userId)
        {
            try
            {
                var notification = new Notification
                {
                    TypeId = "2",
                    IsRead = false,
                    CreatedDate = DateTime.Now
                };

                _context.Notifications.Add(notification);
                await _context.SaveChangesAsync();
                return notification.Id;
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Ошибка при добавлении уведомления: {ex.Message}");
                return -1;
            }
        }

        // Метод для пометки уведомления как прочитанного
        private async Task MarkNotificationAsReadAsync(int notificationId)
        {
            try
            {
                var notification = await _context.Notifications.FindAsync(notificationId);
                if (notification != null)
                {
                    notification.IsRead = true;
                    await _context.SaveChangesAsync();
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"Ошибка при обновлении уведомления: {ex.Message}");
            }
        }

        // Улучшенное сообщение с кнопкой OK
        private async Task ShowNotificationMessage(string message, string color, int notificationId)
        {
            var messageWindow = new Window
            {
                WindowStyle = WindowStyle.None,
                AllowsTransparency = true,
                Background = Brushes.Transparent,
                Width = 350,
                Height = 150,
                WindowStartupLocation = WindowStartupLocation.CenterOwner,
                Owner = this
            };

            var border = new Border
            {
                Background = new SolidColorBrush((Color)ColorConverter.ConvertFromString(color)),
                CornerRadius = new CornerRadius(12),
                Margin = new Thickness(10),
                BorderBrush = new SolidColorBrush(Colors.White),
                BorderThickness = new Thickness(2)
            };

            var content = new StackPanel
            {
                VerticalAlignment = VerticalAlignment.Center,
                HorizontalAlignment = HorizontalAlignment.Center,
                Margin = new Thickness(20)
            };

            var textBlock = new TextBlock
            {
                Text = message,
                FontFamily = new FontFamily("Segoe UI"),
                FontSize = 16,
                Foreground = Brushes.White,
                TextAlignment = TextAlignment.Center,
                TextWrapping = TextWrapping.Wrap,
                Margin = new Thickness(0, 0, 0, 15)
            };

            var button = new Button
            {
                Content = "OK",
                Width = 80,
                Height = 30,
                Background = Brushes.White,
                Foreground = new SolidColorBrush((Color)ColorConverter.ConvertFromString(color)),
                FontWeight = FontWeights.Bold,
                BorderThickness = new Thickness(0),
                Cursor = Cursors.Hand
            };

            button.Click += async (s, args) =>
            {
                if (notificationId > 0)
                {
                    await MarkNotificationAsReadAsync(notificationId);
                }
                messageWindow.Close();
            };

            content.Children.Add(textBlock);
            content.Children.Add(button);
            border.Child = content;
            messageWindow.Content = border;

            border.MouseDown += async (s, args) =>
            {
                if (notificationId > 0)
                {
                    await MarkNotificationAsReadAsync(notificationId);
                }
                messageWindow.Close();
            };

            messageWindow.ShowDialog();
        }

        private void ShowStyledMessage(string message, string color)
        {
            var messageWindow = new Window
            {
                WindowStyle = WindowStyle.None,
                AllowsTransparency = true,
                Background = Brushes.Transparent,
                Width = 300,
                Height = 100,
                WindowStartupLocation = WindowStartupLocation.CenterOwner,
                Owner = this
            };

            var border = new Border
            {
                Background = new SolidColorBrush((Color)ColorConverter.ConvertFromString(color)),
                CornerRadius = new CornerRadius(8),
                Margin = new Thickness(10)
            };

            var content = new StackPanel
            {
                VerticalAlignment = VerticalAlignment.Center,
                HorizontalAlignment = HorizontalAlignment.Center
            };

            var textBlock = new TextBlock
            {
                Text = message,
                FontFamily = new FontFamily("Segoe UI"),
                FontSize = 14,
                Foreground = Brushes.White,
                TextAlignment = TextAlignment.Center,
                TextWrapping = TextWrapping.Wrap
            };

            content.Children.Add(textBlock);
            border.Child = content;
            messageWindow.Content = border;

            var timer = new System.Windows.Threading.DispatcherTimer();
            timer.Interval = TimeSpan.FromSeconds(3);
            timer.Tick += (s, args) =>
            {
                timer.Stop();
                messageWindow.Close();
            };
            timer.Start();

            messageWindow.Show();
        }

        private void NavigateLink_Click(object sender, RoutedEventArgs e)
        {
            LoginWindow loginWindow = new LoginWindow();
            loginWindow.Show();
            Close();
        }

        private void UsernameInput_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.Key == Key.Enter)
            {
                PasswordInput.Focus();
            }
        }

        private void PasswordInput_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.Key == Key.Enter && RegisterButton.IsEnabled)
            {
                Register_Click(sender, e);
            }
        }
    }
}