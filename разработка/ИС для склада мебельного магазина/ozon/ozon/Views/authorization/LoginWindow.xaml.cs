using System;
using System.Collections.Generic;
using System.Data.Entity;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;
using ozon.Models;

namespace ozon.Views
{
    /// <summary>
    /// Логика взаимодействия для LoginWindow.xaml
    /// </summary>
    public partial class LoginWindow : Window
    {
        OzonContext _context;

        public LoginWindow()
        {
            InitializeComponent();
            _context = new OzonContext();
            _context.Users.Load();
        }

        private async void Login_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                if (!string.IsNullOrEmpty(UsernameInput.Text) && !string.IsNullOrEmpty(PasswordInput.Password))
                {
                    var user = await _context.Users.FirstOrDefaultAsync(u => u.Username == UsernameInput.Text);

                    if (user != null && BCrypt.Net.BCrypt.Verify(PasswordInput.Password, user.PasswordHash))
                    {
                        StaticUser.UserId = user.Id;
                        StaticUser.Username = user.Username;

                        // Добавляем запись в UserActions
                        await LogUserLoginAsync(user.Id);

                        // Добавляем уведомление о входе и получаем его ID
                        int notificationId = await AddLoginNotificationAsync(user.Id);

                        // Получаем текст уведомления из NotificationType где Id = 1
                        var notificationType = await _context.NotificationTypes.FirstOrDefaultAsync(nt => nt.Id == 1);
                        string notificationMessage = notificationType?.TypeName ?? "Успешная авторизация!";

                        // Показываем улучшенное сообщение с кнопкой OK
                        await ShowNotificationMessage(notificationMessage, "#2E8B57", notificationId);

                        if (user.UserRole == "admin")
                        {
                            MainAdminWindow adminWindow = new MainAdminWindow();
                            adminWindow.Show();
                        }
                        else if (user.UserRole == "worker")
                        {
                            StaticUser.Address = user.Address;
                            MainWorkerWindow workerWindow = new MainWorkerWindow();
                            workerWindow.Show();
                        }
                        Close();
                    }
                    else
                    {
                        ShowStyledMessage("Неверное имя пользователя или пароль.", "#CD5C5C");
                    }
                }
                else
                {
                    ShowStyledMessage("Заполните все поля.", "#CD5C5C");
                }
            }
            catch (Exception ex)
            {
                ShowStyledMessage($"Ошибка авторизации: {ex.Message}", "#CD5C5C");
            }
        }

        // Метод для логирования входа пользователя
        private async Task LogUserLoginAsync(int userId)
        {
            try
            {
                var userAction = new UserAction
                {
                    ActionTypeId = "1", // 1 для логина
                    ActionDate = DateTime.Now,
                    UserId = userId
                };

                _context.UserActions.Add(userAction);
                await _context.SaveChangesAsync();
            }
            catch (Exception ex)
            {
                // Логируем ошибку, но не прерываем процесс логина
                System.Diagnostics.Debug.WriteLine($"Ошибка при логировании входа: {ex.Message}");
            }
        }

        // Метод для добавления уведомления о входе (возвращает ID созданного уведомления)
        private async Task<int> AddLoginNotificationAsync(int userId)
        {
            try
            {
                var notification = new Notification
                {
                    TypeId = "1", // TypeId = 1 для уведомлений о входе
                    IsRead = false,
                    CreatedDate = DateTime.Now
                };

                _context.Notifications.Add(notification);
                await _context.SaveChangesAsync();
                return notification.Id; // Возвращаем ID созданного уведомления
            }
            catch (Exception ex)
            {
                // Логируем ошибку, но не прерываем процесс логина
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

            // Обработчик нажатия кнопки OK
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

            // Также закрываем по клику на любую область сообщения
            border.MouseDown += async (s, args) =>
            {
                if (notificationId > 0)
                {
                    await MarkNotificationAsReadAsync(notificationId);
                }
                messageWindow.Close();
            };

            // Показываем модально, чтобы пользователь обязательно увидел сообщение
            messageWindow.ShowDialog();
        }

        // Старый метод для обычных сообщений (без кнопки OK)
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
            };

            content.Children.Add(textBlock);
            border.Child = content;
            messageWindow.Content = border;

            // Автоматическое закрытие сообщения через 3 секунды
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
            RegistrationWindow registrationWindow = new RegistrationWindow();
            registrationWindow.Show();
            Close();
        }

        // Улучшенная обработка нажатия Enter для полей ввода
        private void UsernameInput_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.Key == Key.Enter)
            {
                PasswordInput.Focus();
            }
        }

        private void PasswordInput_KeyDown(object sender, KeyEventArgs e)
        {
            if (e.Key == Key.Enter)
            {
                Login_Click(sender, e);
            }
        }
    }
}